/* MarkerPlayback v1.1 - time-shifted playback, single rAF loop for all devices */
(function (w) {
  'use strict';
  if (w.MARKER_PLAYBACK === false) return;

  var CFG = {
    LAG_FACTOR: 1.25,
    MIN_LAG: 15000,
    MAX_LAG: 240000,
    RATE_MIN: 0.6,
    RATE_MAX: 1.6,
    MAX_SPEED_KMH: 250,
    MAX_POINTS: 12,
    FPS_MS: 33,
    CLUSTER_SYNC_MS: 3000
  };

  var devs = {};
  var running = false, lastFrame = 0, lastSync = 0, prev = 0;
  var clusterGroup = null;

  function hav(a, b) {
    var R = 6371000, r = Math.PI / 180;
    var dLat = (b.lat - a.lat) * r, dLng = (b.lng - a.lng) * r;
    var s = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(a.lat * r) * Math.cos(b.lat * r) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return 2 * R * Math.asin(Math.sqrt(s));
  }
  function bearing(a, b) {
    var r = Math.PI / 180, y = Math.sin((b.lng - a.lng) * r) * Math.cos(b.lat * r);
    var x = Math.cos(a.lat * r) * Math.sin(b.lat * r) -
      Math.sin(a.lat * r) * Math.cos(b.lat * r) * Math.cos((b.lng - a.lng) * r);
    return (Math.atan2(y, x) / r + 360) % 360;
  }
  function clamp(v, lo, hi) { return Math.max(lo, Math.min(hi, v)); }

  function decode(str, prec) {
    var idx = 0, lat = 0, lng = 0, out = [], f = Math.pow(10, prec || 6);
    while (idx < str.length) {
      for (var k = 0; k < 2; k++) {
        var b, shift = 0, res = 0;
        do { b = str.charCodeAt(idx++) - 63; res |= (b & 31) << shift; shift += 5; } while (b >= 32);
        var d = (res & 1) ? ~(res >> 1) : (res >> 1);
        if (k === 0) lat += d; else lng += d;
      }
      out.push({ lat: lat / f, lng: lng / f });
    }
    return out;
  }

  function alongPath(path, f) {
    if (!path || path.length < 2) return null;
    if (!path._len) {
      var acc = [0];
      for (var i = 1; i < path.length; i++) acc[i] = acc[i - 1] + hav(path[i - 1], path[i]);
      path._len = acc;
    }
    var total = path._len[path.length - 1];
    if (total === 0) return path[0];
    var target = f * total;
    for (var j = 1; j < path.length; j++) {
      if (path._len[j] >= target) {
        var seg = path._len[j] - path._len[j - 1] || 1;
        var u = (target - path._len[j - 1]) / seg;
        return {
          lat: path[j - 1].lat + (path[j].lat - path[j - 1].lat) * u,
          lng: path[j - 1].lng + (path[j].lng - path[j - 1].lng) * u,
          brg: bearing(path[j - 1], path[j])
        };
      }
    }
    return path[path.length - 1];
  }

  function getDev(id, marker) {
    var d = devs[id];
    if (!d) d = devs[id] = { id: id, pts: [], playT: null, emaGap: 120000, marker: marker, syncedLL: null, lastRender: null };
    if (marker) d.marker = marker;
    return d;
  }

  function push(id, marker, fixes) {
    var d = getDev(id, marker);
    fixes.forEach(function (fx) {
      var last = d.pts[d.pts.length - 1];
      if (last && fx.t <= last.t) return;
      if (last) {
        var dt = (fx.t - last.t) / 3600000, km = hav(last, fx) / 1000;
        if (dt > 0 && km / dt > CFG.MAX_SPEED_KMH && km > 0.2) return;
        d.emaGap = d.emaGap * 0.7 + (fx.t - last.t) * 0.3;
        if (fx.geom) fx.path = decode(fx.geom, 6);
      }
      d.pts.push(fx);
    });
    if (d.pts.length > CFG.MAX_POINTS) d.pts.splice(0, d.pts.length - CFG.MAX_POINTS);
    if (d.playT === null && d.pts.length) {
      d.playT = d.pts[d.pts.length - 1].t;
      render(d, d.pts[d.pts.length - 1], null);
    }
    start();
  }

  function render(d, ll, brg) {
    var m = d.marker;
    if (!m) return;
    if (m.slideCancel) m.slideCancel();
    m._latlng = L.latLng(ll.lat, ll.lng);
    if (m.update) m.update();
    if (brg != null && m.setRotationAngle) m.setRotationAngle(brg);
    d.lastRender = { lat: ll.lat, lng: ll.lng };
  }

  function step(d, dt) {
    var pts = d.pts, n = pts.length;
    if (n < 2) return;
    var newest = pts[n - 1].t;
    var lagTarget = clamp(d.emaGap * CFG.LAG_FACTOR, CFG.MIN_LAG, CFG.MAX_LAG);
    var lag = newest - d.playT;

    if (dt > 2000) d.playT = Math.max(d.playT, newest - lagTarget);
    var rate = clamp(lag / lagTarget, CFG.RATE_MIN, CFG.RATE_MAX);
    var next = Math.min(d.playT + dt * rate, newest);
    if (next <= d.playT) return;
    d.playT = next;

    var i = n - 2;
    while (i > 0 && pts[i].t > d.playT) i--;
    var a = pts[i], b = pts[i + 1];
    var f = clamp((d.playT - a.t) / (b.t - a.t), 0, 1);
    var p = b.path ? alongPath(b.path, f) : null;
    var pos = p || { lat: a.lat + (b.lat - a.lat) * f, lng: a.lng + (b.lng - a.lng) * f };
    render(d, pos, p && p.brg != null ? p.brg : (hav(a, b) > 5 ? bearing(a, b) : null));
  }

  function syncCluster(now) {
    if (!clusterGroup || !clusterGroup._moveChild || now - lastSync < CFG.CLUSTER_SYNC_MS) return;
    lastSync = now;
    Object.keys(devs).forEach(function (k) {
      var d = devs[k];
      if (!d.marker || !d.lastRender) return;
      if (d.marker._popup && d.marker._popup.isOpen && d.marker._popup.isOpen()) return;
      var nl = L.latLng(d.lastRender.lat, d.lastRender.lng);
      if (d.syncedLL && d.syncedLL.distanceTo(nl) < 3) return;
      if (d.syncedLL && clusterGroup.hasLayer(d.marker)) clusterGroup._moveChild(d.marker, d.syncedLL, nl);
      d.syncedLL = nl;
    });
  }

  function frame(now) {
    if (!running) return;
    if (now - lastFrame >= CFG.FPS_MS) {
      var dt = prev ? now - prev : 0; prev = now; lastFrame = now;
      var active = false;
      Object.keys(devs).forEach(function (k) {
        var d = devs[k];
        if (d.pts.length > 1 && d.playT < d.pts[d.pts.length - 1].t) { active = true; step(d, dt); }
      });
      syncCluster(now);
      if (!active) { running = false; prev = 0; return; }
    }
    requestAnimationFrame(frame);
  }
  function start() { if (!running) { running = true; requestAnimationFrame(frame); } }

  w.MarkerPlayback = {
    push: push,
    remove: function (id) { delete devs[id]; },
    setClusterGroup: function (g) { clusterGroup = g; },
    state: function () { return devs; },
    cfg: CFG
  };
})(window);

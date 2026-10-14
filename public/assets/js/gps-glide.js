/* gps-glide.js v2.2 — replay recorded fixes, then project forward between batches.
 * v2.1 behavior preserved (replay NEW recorded fixes, follow the real road).
 * New: after replay finishes, if the newest fix had a real speed, keep the
 * marker sliding along the last course at that speed for up to projMaxSec
 * seconds or projMaxM meters, whichever comes first. When the next batch
 * arrives, the marker smoothly continues onto the real path.
 * Bounds prevent the marker drifting across Pakistan during a long silence.
 * Off:  localStorage.setItem('gpsGlide','0'); location.reload();
 * On:   localStorage.removeItem('gpsGlide')
 */
(function (global) {
    'use strict';

    var cfg = {
        minMoveM: 8,
        snapM: 1500,
        pathSnapM: 8000,
        minDurMs: 1500,
        maxDurMs: 1200000,
        alpha: 0.35,
        minSpeedMs: 0.3,
        maxSpeedMs: 45,
        fallbackSpeedMs: 12,
        minPointM: 5,
        spikeM: 80,
        projMaxSec: 120,
        projMaxM: 1500,
        projMinSpeedMs: 1.4
    };

    var states = new WeakMap(),
        active = new Set(),
        log = [],
        raf = null;

    function isOn() {
        try { return global.localStorage.getItem('gpsGlide') !== '0'; } catch (e) { return true; }
    }

    function note(layer, action, meters, durMs, emaMs, track, nPts) {
        log.push({
            t: new Date().toLocaleTimeString(),
            dev: layer.options && layer.options.device_id,
            action: action,
            m: Math.round(meters),
            durS: Math.round((durMs || 0) / 100) / 10,
            emaS: Math.round((emaMs || 0) / 100) / 10,
            tr: track && track.length ? track.length : 0,
            pts: nPts || 0
        });
        if (log.length > 80) log.shift();
    }

    function maxId(track) {
        var m = -1, i, id;
        if (!track || !track.length) return -1;
        for (i = 0; i < track.length; i++) {
            id = +track[i].id;
            if (!isFinite(id)) return -1;
            if (id > m) m = id;
        }
        return m;
    }

    function buildCum(pts) {
        var cum = [0], i;
        for (i = 1; i < pts.length; i++) cum.push(cum[i - 1] + pts[i - 1].distanceTo(pts[i]));
        return cum;
    }

    function pointAt(s, dist) {
        var pts = s.pts, cum = s.cum, n = pts.length, i = 1, a, b, seg, f;
        if (dist <= 0) return pts[0];
        if (dist >= s.len) return pts[n - 1];
        while (i < n - 1 && cum[i] < dist) i++;
        a = pts[i - 1]; b = pts[i];
        seg = cum[i] - cum[i - 1];
        f = seg > 0 ? (dist - cum[i - 1]) / seg : 1;
        return L.latLng(a.lat + (b.lat - a.lat) * f, a.lng + (b.lng - a.lng) * f);
    }

    function remaining(s, now, cur) {
        var out = [cur], progress, i;
        if (!s.playing || !s.pts) return out;
        progress = Math.min(1, Math.max(0, (now - s.t0) / s.dur)) * s.len;
        for (i = 1; i < s.pts.length; i++) if (s.cum[i] > progress) out.push(s.pts[i]);
        return out;
    }

    function clean(pts) {
        var out = [pts[0]], i, p, a, b, da, db, m;
        for (i = 1; i < pts.length; i++) {
            p = pts[i];
            if (i < pts.length - 1) {
                a = out[out.length - 1]; b = pts[i + 1];
                da = a.distanceTo(p); db = p.distanceTo(b); m = Math.min(da, db);
                if (m > cfg.spikeM && (da + db) > 2.5 * a.distanceTo(b)) continue;
                if (da < cfg.minPointM) continue;
            }
            out.push(p);
        }
        return out;
    }

    function bearing(a, b) {
        var lat1 = a.lat * Math.PI / 180;
        var lat2 = b.lat * Math.PI / 180;
        var dLng = (b.lng - a.lng) * Math.PI / 180;
        var y = Math.sin(dLng) * Math.cos(lat2);
        var x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);
        return Math.atan2(y, x);
    }

    function waypoints(s, track, pos) {
        var out = [], usable = false, i, r, id, lat, lng, lastSp = null, lastCo = null;
        if (track && track.length && s.lastId >= 0) {
            usable = true;
            for (i = 0; i < track.length; i++) {
                r = track[i]; id = +r.id; lat = +r.lat; lng = +r.lng;
                if (!isFinite(id) || !isFinite(lat) || !isFinite(lng)) { out = []; usable = false; break; }
                if (id > s.lastId) {
                    out.push(L.latLng(lat, lng));
                    if (r.sp != null) lastSp = +r.sp;
                    if (r.co != null) lastCo = +r.co;
                }
            }
        }
        if (track) s.lastId = Math.max(s.lastId, maxId(track));
        if (out.length) return { pts: out, real: true, lastSp: lastSp, lastCo: lastCo };
        if (usable) return null;
        return { pts: [pos], real: false, lastSp: null, lastCo: null };
    }

    function tick(now) {
        raf = null;
        active.forEach(function (layer) {
            var s = states.get(layer), p, elapsedSec, maxSec, distM, rad, latRad, dLat, dLng;
            if (!s || !layer._map) { active.delete(layer); return; }

            if (s.playing) {
                p = (now - s.t0) / s.dur;
                if (p < 1) {
                    if (p < 0) p = 0;
                    layer.setLatLng(pointAt(s, p * s.len));
                    return;
                }
                var last = s.pts[s.pts.length - 1];
                layer.setLatLng(last);
                s.playing = false;
                if (s.proj && s.proj.speedMs >= cfg.projMinSpeedMs) {
                    s.projStart = now;
                    s.projOrigin = last;
                    s.projecting = true;
                    return;
                }
                active.delete(layer);
                return;
            }

            if (s.projecting) {
                elapsedSec = (now - s.projStart) / 1000;
                maxSec = Math.min(cfg.projMaxSec, cfg.projMaxM / Math.max(s.proj.speedMs, 0.1));
                if (elapsedSec >= maxSec) {
                    s.projecting = false;
                    active.delete(layer);
                    return;
                }
                distM = s.proj.speedMs * elapsedSec;
                rad = s.proj.courseRad;
                latRad = s.projOrigin.lat * Math.PI / 180;
                dLat = (distM * Math.cos(rad)) / 111000;
                dLng = (distM * Math.sin(rad)) / (111000 * Math.cos(latRad));
                layer.setLatLng([s.projOrigin.lat + dLat, s.projOrigin.lng + dLng]);
                return;
            }

            active.delete(layer);
        });
        if (active.size) raf = global.requestAnimationFrame(tick);
    }

    function move(layer, pos, hint) {
        var now = performance.now(),
            s = states.get(layer),
            track = hint && hint.track,
            w, wp, real, target, d, gap, cur, pts, cum, len, sample, v, dur, hidden,
            projSpeedMs = 0, projCourseRad = 0, n;

        pos = L.latLng(pos);

        if (!s) {
            states.set(layer, {
                layer: layer, last: pos, arrive: now, ema: null, lastId: maxId(track),
                pts: null, cum: null, len: 0, t0: 0, dur: 1, playing: false,
                proj: null, projecting: false, projStart: 0, projOrigin: null
            });
            layer.setLatLng(pos);
            note(layer, 'init', 0, 0, 0, track, 0);
            return;
        }

        w = waypoints(s, track, pos);
        if (!w) return;
        wp = w.pts;
        real = w.real;
        target = wp[wp.length - 1];

        d = s.last.distanceTo(target);
        if (d < cfg.minMoveM) return;

        if (real) {
            if (w.lastSp != null && w.lastCo != null && w.lastSp > 0) {
                projSpeedMs = w.lastSp * 0.514444;
                projCourseRad = w.lastCo * Math.PI / 180;
            } else if (wp.length >= 2) {
                n = wp.length;
                projSpeedMs = wp[n - 2].distanceTo(wp[n - 1]) / 3;
                projCourseRad = bearing(wp[n - 2], wp[n - 1]);
            }
        }

        gap = now - s.arrive;
        s.last = target;
        s.arrive = now;

        cur = layer.getLatLng();
        hidden = document.hidden;

        if (hidden || cur.distanceTo(target) > (real ? cfg.pathSnapM : cfg.snapM)) {
            s.playing = false;
            s.projecting = false;
            s.proj = null;
            active.delete(layer);
            layer.setLatLng(target);
            note(layer, hidden ? 'snap:hidden' : 'snap:far', d, 0, s.ema, track, wp.length);
            return;
        }

        pts = clean((real ? remaining(s, now, cur) : [cur]).concat(wp));
        cum = buildCum(pts);
        len = cum[cum.length - 1];

        sample = Math.min(Math.max(gap, cfg.minDurMs), cfg.maxDurMs);
        v = len / (sample / 1000);
        if (v >= 0.2 && v <= cfg.maxSpeedMs) {
            s.ema = (s.ema === null) ? sample : s.ema + cfg.alpha * (sample - s.ema);
        }

        dur = (s.ema === null) ? len / cfg.fallbackSpeedMs * 1000 : s.ema;
        dur = Math.min(dur, len / cfg.minSpeedMs * 1000);
        dur = Math.max(dur, len / cfg.maxSpeedMs * 1000);
        dur = Math.min(Math.max(dur, cfg.minDurMs), cfg.maxDurMs);

        s.pts = pts; s.cum = cum; s.len = len;
        s.t0 = now; s.dur = dur; s.playing = true;
        s.projecting = false;
        s.proj = (real && projSpeedMs > 0) ? { speedMs: projSpeedMs, courseRad: projCourseRad } : null;
        active.add(layer);
        if (!raf) raf = global.requestAnimationFrame(tick);
        note(layer, real ? 'path' : 'glide', len, dur, s.ema, track, pts.length);
    }

    function forget(layer) { active.delete(layer); states.delete(layer); }

    global.GpsGlide = {
        move: move,
        forget: forget,
        isOn: isOn,
        cfg: cfg,
        log: log,
        activeCount: function () { return active.size; },
        debug: function (layer) {
            var s = states.get(layer);
            return s && {
                lastId: s.lastId,
                playing: s.playing,
                projecting: s.projecting,
                pts: s.pts && s.pts.length,
                lenM: Math.round(s.len),
                durS: Math.round(s.dur / 100) / 10
            };
        }
    };
})(window);

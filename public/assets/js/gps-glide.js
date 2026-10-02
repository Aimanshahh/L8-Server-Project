/* gps-glide.js - small "render slightly behind" glide for device markers.
 * Alternative to gps-smooth.js. Uses only browser arrival times (never device timestamps)
 * and a single requestAnimationFrame loop for all markers.
 * Off:  localStorage.setItem('gpsGlide','0'); location.reload();   On: localStorage.removeItem('gpsGlide')
 */
(function (global) {
    'use strict';

    var cfg = {
        minMoveM: 8,            // smaller "moves" are GPS jitter / duplicates: ignored
        snapM: 3000,            // bigger jumps (reconnect after a long gap): snap, never fly
        minDurMs: 1500,
        maxDurMs: 1200000,
        alpha: 0.35,            // smoothing of the observed fix interval
        minSpeedMs: 0.3,        // never crawl slower than ~5 km/h
        maxSpeedMs: 45,         // never glide faster than ~160 km/h
        fallbackSpeedMs: 12     // pace used until an interval has been learned (~43 km/h)
    };

    var states = new WeakMap(),
        active = new Set(),
        log = [],
        raf = null;

    function isOn() {
        try { return global.localStorage.getItem('gpsGlide') !== '0'; } catch (e) { return true; }
    }

    function note(layer, action, meters, durMs, emaMs) {
        log.push({
            t: new Date().toLocaleTimeString(),
            dev: layer.options && layer.options.device_id,
            action: action,
            m: Math.round(meters),
            durS: Math.round((durMs || 0) / 100) / 10,
            emaS: Math.round((emaMs || 0) / 100) / 10
        });
        if (log.length > 80) log.shift();
    }

    function tick(now) {
        raf = null;
        active.forEach(function (layer) {
            var s = states.get(layer);
            if (!s || !layer._map) { active.delete(layer); return; }
            var p = (now - s.t0) / s.dur;
            if (p < 0) p = 0;
            if (p >= 1) {
                layer.setLatLng(s.to);
                active.delete(layer);
                return;
            }
            layer.setLatLng(L.latLng(
                s.from.lat + (s.to.lat - s.from.lat) * p,
                s.from.lng + (s.to.lng - s.from.lng) * p
            ));
        });
        if (active.size) raf = global.requestAnimationFrame(tick);
    }

    function move(layer, pos) {
        var now = performance.now(),
            s = states.get(layer);

        pos = L.latLng(pos);

        if (!s) {                                   // first sighting: just place it
            states.set(layer, { last: pos, arrive: now, ema: null });
            layer.setLatLng(pos);
            note(layer, 'init', 0, 0, 0);
            return;
        }

        var d = s.last.distanceTo(pos);
        if (d < cfg.minMoveM) return;               // duplicate / jitter: leave any glide alone

        var gap = now - s.arrive;
        s.last = pos;
        s.arrive = now;

        if (document.hidden || d > cfg.snapM) {
            active.delete(layer);
            layer.setLatLng(pos);
            note(layer, document.hidden ? 'snap:hidden' : 'snap:far', d, 0, s.ema);
            return;
        }

        // learn the fix interval, but only from believable samples
        var sample = Math.min(Math.max(gap, cfg.minDurMs), cfg.maxDurMs),
            v = d / (sample / 1000);
        if (v >= 0.2 && v <= cfg.maxSpeedMs) {
            s.ema = (s.ema === null) ? sample : s.ema + cfg.alpha * (sample - s.ema);
        }

        var dur = (s.ema === null) ? d / cfg.fallbackSpeedMs * 1000 : s.ema;
        dur = Math.min(dur, d / cfg.minSpeedMs * 1000);     // don't crawl
        dur = Math.max(dur, d / cfg.maxSpeedMs * 1000);     // don't fly
        dur = Math.min(Math.max(dur, cfg.minDurMs), cfg.maxDurMs);

        s.from = layer.getLatLng();                 // where it is drawn right now
        s.to = pos;
        s.t0 = now;
        s.dur = dur;
        active.add(layer);
        if (!raf) raf = global.requestAnimationFrame(tick);
        note(layer, 'glide', d, dur, s.ema);
    }

    function forget(layer) { active.delete(layer); states.delete(layer); }

    global.GpsGlide = {
        move: move,
        forget: forget,
        isOn: isOn,
        cfg: cfg,
        log: log,
        activeCount: function () { return active.size; }
    };
})(window);

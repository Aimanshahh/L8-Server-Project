/* eslint-disable no-console */
/**
 * gps-smooth engine tests - replays every pattern from the real 90 minute log
 * and asserts the required behaviour of the marker glide + follow mode.
 *
 * Run:  node tests/js/gps-smooth.test.js
 *
 * No dependencies: the engine is loaded with a stubbed window/document, a fake
 * clock and a fake animation scheduler, so the whole thing is deterministic.
 */

'use strict';

const path = require('path');

// ---------------------------------------------------------------- test harness
let passed = 0, failed = 0;
const failures = [];

function t(name, fn) {
    try {
        fn();
        passed++;
        console.log('  \u2713 ' + name);
    } catch (e) {
        failed++;
        failures.push(name + ' :: ' + e.message);
        console.log('  \u2717 ' + name + '\n      -> ' + e.message);
    }
}

function ok(cond, msg) {
    if (!cond) throw new Error(msg || 'expected true');
    return true;
}

function near(actual, expected, tol, msg) {
    if (Math.abs(actual - expected) > tol) {
        throw new Error((msg || 'not near') + ' (got ' + actual + ', expected ' + expected + ' \u00b1 ' + tol + ')');
    }
    return true;
}

// Comments are prose: the static checks below scan executable code only.
function stripComments(src) {
    return src.replace(/\/\*[\s\S]*?\*\//g, '').replace(/(^|[^:])\/\/[^\n]*/g, '$1');
}

function section(title) { console.log('\n' + title); }

// ------------------------------------------------------------- fake environment
const clock = { now: Date.UTC(2026, 9, 1, 5, 0, 0) };   // 2026-10-01 05:00:00Z
const BASE_SEC = Math.floor(clock.now / 1000);          // trackers report seconds

let pending = null;

const docHandlers = {};
global.document = {
    hidden: false,
    addEventListener: function (ev, fn) { docHandlers[ev] = fn; }
};
global.window = global;

const ENGINE_PATH = path.join(__dirname, '..', '..', 'public', 'assets', 'js', 'gps-smooth.js');
const fs = require('fs');
const engineSource = fs.readFileSync(ENGINE_PATH, 'utf8');
require(ENGINE_PATH);

const GS = global.GpsSmooth;
const CFG = GS.cfg;

CFG.now = function () { return clock.now; };
CFG.scheduler = {
    request: function (fn) { pending = fn; return 1; },
    cancel: function () { pending = null; }
};
CFG.debug = false;

function frame(ms) {
    clock.now += (ms === undefined ? 16.7 : ms);
    const fn = pending;
    pending = null;
    if (fn) fn();
}

function frames(count, ms) {
    for (let i = 0; i < count; i++) frame(ms);
}

function advance(ms) { clock.now += ms; }

// ------------------------------------------------------------------ fake Leaflet
function makeMap(w, h) {
    const size = { x: w || 1000, y: h || 700 };
    const map = {
        _animatingZoom: false,
        centerWorld: { x: 0, y: 0 },
        scale: 1000,                     // world px per degree
        size: size,
        pans: [],
        setViewCalls: [],
        panToCalls: [],
        handlers: {},
        _leaflet_id: 999,
        worldPx: function (ll) { return { x: ll.lng * this.scale, y: -ll.lat * this.scale }; },
        getSize: function () { return size; },
        hasLayer: function (l) { return l._map === this; },
        latLngToContainerPoint: function (ll) {
            const w = this.worldPx(ll);
            return { x: w.x - this.centerWorld.x + size.x / 2, y: w.y - this.centerWorld.y + size.y / 2 };
        },
        panBy: function (offset, opts) {
            this.pans.push({ dx: offset[0], dy: offset[1], animate: opts && opts.animate, t: clock.now });
            // Leaflet semantics (verified against Leaflet 1.0.3): panBy moves the
            // content by `offset`, so a fixed marker moves by -offset on screen.
            this.centerWorld.x += offset[0];
            this.centerWorld.y += offset[1];
            return this;
        },
        panTo: function (ll, opts) { this.panToCalls.push({ ll: ll, opts: opts }); return this; },
        setView: function () { this.setViewCalls.push([].slice.call(arguments)); return this; },
        once: function (ev, fn) { this.handlers[ev] = fn; return this; },
        on: function (ev, fn) { this.handlers[ev] = fn; return this; },
        off: function () { return this; },
        fire: function (ev) { if (this.handlers[ev]) this.handlers[ev](); return this; }
    };
    return map;
}

function makeMarker(map, lat, lng, id) {
    const m = {
        _map: map,
        _leaflet_id: Math.floor(Math.random() * 1e6),
        options: { device_id: id || 1 },
        _latlng: { lat: lat, lng: lng },
        moves: 0,
        setLatLngs: [] ,
        setLatLng: function (p) {
            this._latlng = { lat: p.lat, lng: p.lng };
            this.moves++;
            this.setLatLngs.push({ lat: p.lat, lng: p.lng, t: clock.now });
        },
        getLatLng: function () { return { lat: this._latlng.lat, lng: this._lng === undefined ? this._latlng.lng : this._lng }; }
    };
    m.getLatLng = function () { return { lat: m._latlng.lat, lng: m._latlng.lng }; };
    return m;
}

function fresh(name) {
    GS._reset();
    pending = null;
    const map = makeMap();
    const marker = makeMarker(map, 34.1743422, 73.2332572, name || 1);
    return { map: map, marker: marker };
}

// Great-circle distance, mirrors the engine math.
function meters(a, b) {
    const R = 6371008.8, DEG = Math.PI / 180;
    const dLat = (b.lat - a.lat) * DEG, dLng = (b.lng - a.lng) * DEG;
    const h = Math.sin(dLat / 2) ** 2 +
        Math.cos(a.lat * DEG) * Math.cos(b.lat * DEG) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
}

// Move `from` by `m` meters on `bearingDeg` (test helper for building log points).
function project(from, bearingDeg, m) {
    const R = 6371008.8, DEG = Math.PI / 180;
    const d = m / R, brg = bearingDeg * DEG, la1 = from.lat * DEG, lo1 = from.lng * DEG;
    const la2 = Math.asin(Math.sin(la1) * Math.cos(d) + Math.cos(la1) * Math.sin(d) * Math.cos(brg));
    const lo2 = lo1 + Math.atan2(Math.sin(brg) * Math.sin(d) * Math.cos(la1), Math.cos(d) - Math.sin(la1) * Math.sin(la2));
    return { lat: la2 / DEG, lng: ((lo2 / DEG) + 540) % 360 - 180 };
}

console.log('GpsSmooth v' + GS.version + ' engine tests  (engine: ' + ENGINE_PATH + ')');

// =============================================================== static checks
section('Static safety checks (the "known problems to avoid" list)');

const engineCode = stripComments(engineSource);

t('engine never touches CSS transitions on markers', () => {
    ok(!/style\.transition|transition\s*:/.test(engineCode), 'engine must not use CSS transitions');
});
t('engine never calls slideTo (no restart-based animation)', () => {
    ok(!/slideTo/.test(engineSource), 'engine must not depend on Leaflet.Marker.SlideTo');
});
t('engine never calls map.setView / fitBounds for following', () => {
    ok(!/\.setView\(|fitBounds\(/.test(engineCode), 'follow mode must pan, never snap');
});
t('engine uses no hard-coded 3000/60000 duration clamp', () => {
    ok(!/Math\.max\(3000/.test(engineCode), 'old hard-coded duration clamp must be gone');
});
t('app.js routes device movement through the engine', () => {
    const app = fs.readFileSync(path.join(__dirname, '..', '..', 'public', 'assets', 'js', 'app.js'), 'utf8');
    ok(/window\.GpsSmooth\.move\(layer, position/.test(app), 'Device.updateLayer must call GpsSmooth.move');
    ok(!/layer\.slideTo\(position/.test(app), 'no live slideTo calls may remain');
    ok(/win\.GpsSmoothLegacy = \{/.test(app), 'the superseded v1 engine must not shadow v2');
});
t('engine is loaded before app.js in every layout that loads app.js', () => {
    ['loged', 'default', 'sharing'].forEach((name) => {
        const src = fs.readFileSync(path.join(__dirname, '..', '..', 'Tobuli', 'Views', 'Frontend', 'Layouts', name + '.blade.php'), 'utf8');
        const engineAt = src.indexOf('assets/js/gps-smooth.js');
        const appAt = src.indexOf('assets/js/app.js');
        ok(engineAt > -1 && appAt > -1 && engineAt < appAt, name + ' must load gps-smooth.js before app.js');
    });
});

// ================================================================ first contact
section('Pattern 1 - normal movement (1-5 s cadence): continuous, pace-matched');

t('first known position is placed instantly and stops', () => {
    const { marker } = fresh();
    const p = { lat: 34.1743422, lng: 73.2332572 };
    GS.move(marker, p, { animate: true, ts: BASE_SEC });
    ok(near(meters(marker._latlng, p), 0, 0.01), 'marker must sit exactly on the first fix');
    ok(pending === null, 'a single position must not start an animation loop');
});

t('glide is continuous: every frame moves, never a freeze mid-segment', () => {
    const { marker } = fresh();
    let p = { lat: 34.1743422, lng: 73.2332572 };
    GS.move(marker, p, { animate: true, ts: BASE_SEC });
    marker.setLatLngs.length = 0;

    const next = project(p, 20, 120);           // 120 m in 5 s = 86 km/h
    advance(5000);
    GS.move(marker, next, { animate: true, ts: BASE_SEC + 5 });

    let stalls = 0, prev = marker.getLatLng();
    for (let i = 0; i < 360; i++) {
        frame(16.7);
        const cur = marker.getLatLng();
        const d = meters(prev, cur);
        if (d < 0.001 && meters(cur, next) > 1) stalls++;
        prev = cur;
    }
    ok(stalls === 0, 'marker froze for ' + stalls + ' frames while it still had ground to cover');
    ok(near(meters(marker.getLatLng(), next), 0, 2), 'marker must land on the fix');
});

t('glide duration matches the device cadence, not a hard-coded 3-60 s', () => {
    const { marker } = fresh();
    let p = { lat: 34.2, lng: 73.2 };
    GS.move(marker, p, { animate: true, ts: BASE_SEC });

    const next = project(p, 20, 100);
    advance(4000);
    GS.move(marker, next, { animate: true, ts: BASE_SEC + 4 });

    const st = GS._state(marker);
    ok(st.seg, 'a segment must be running');
    near(st.seg.dur, 4000, 900, 'duration must follow the 4 s cadence');
});

t('a 3 minute cadence glides slowly for the whole gap (no fast-then-freeze)', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.2, lng: 73.2 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });

    const p1 = project(p0, 10, 4000);           // 4 km in 3 min = 80 km/h
    advance(180000);
    GS.move(marker, p1, { animate: true, ts: BASE_SEC + 180 });

    const st = GS._state(marker);
    near(st.seg.dur, 180000, 25000, '3 min cadence must glide for ~3 min');

    frames(60 * 90, 16.7);                      // 90 s of animation
    const done = meters(marker.getLatLng(), p1);
    const todo = meters(marker.getLatLng(), p0);
    ok(todo > 1500 && done > 1500, 'after 90 s the marker must be about halfway, not finished');
    ok(markersPerSecond(marker) < 45, 'pace must stay near the real 22 m/s, got ' + markersPerSecond(marker).toFixed(1));
});

function markersPerSecond(marker) {
    const list = marker.setLatLngs;
    if (list.length < 2) return 0;
    let m = 0;
    for (let i = 1; i < list.length; i++) m += meters(list[i - 1], list[i]);
    const dt = (list[list.length - 1].t - list[0].t) / 1000;
    return dt > 0 ? m / dt : 0;
}

// =============================================================== burst handling
section('Pattern 2 - same-timestamp bursts: newest fix wins, no queue, no jumps');

t('37 fixes in one tick collapse into a single glide to the newest fix', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.1778477, lng: 73.2286183 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });

    advance(1000);
    const first = project(p0, 30, 40);
    GS.move(marker, first, { animate: true, ts: BASE_SEC + 1 });
    const movesAfterFirst = marker.moves;
    const before = marker.getLatLng();

    // 36 more fixes, all stamped the same second, walking away 40 m at a time.
    // (The marker must end up on the newest one, never touring the rest.)
    let last = first;
    for (let i = 0; i < 36; i++) {
        last = project(last, 30, 40);
        GS.move(marker, last, { animate: true, ts: BASE_SEC + 1 });
    }

    const st = GS._state(marker);
    ok(near(meters(st.target, last), 0, 0.01), 'target must be the newest fix of the burst');
    ok(marker.moves - movesAfterFirst <= 2, 'burst must not restart the glide 36 times (restarts=' + (marker.moves - movesAfterFirst) + ')');
    ok(near(meters(marker.getLatLng(), before), 0, 3), 'burst must not visually jump: moved ' + meters(marker.getLatLng(), before).toFixed(1) + ' m');

    // The animation must head straight at the newest fix, never touring the
    // superseded intermediate positions of the burst.
    const gapBefore = meters(marker.getLatLng(), last);
    marker.setLatLngs.length = 0;
    frames(900, 16.7);
    let prevGap = gapBefore;
    marker.setLatLngs.forEach((p) => {
        const gap = meters(p, last);
        ok(gap <= prevGap + 1, 'marker must only ever close in on the newest fix');
        prevGap = gap;
    });
    ok(near(meters(marker.getLatLng(), last), 0, 3), 'marker must settle on the newest fix');
});

t('duplicate coordinates are dropped: no queue, no stutter, no EMA damage', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.2647894, lng: 73.2361511 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });

    const p1 = project(p0, 5, 60);
    advance(5000);
    GS.move(marker, p1, { animate: true, ts: BASE_SEC + 5 });
    frames(600, 16.7);                                  // let the glide finish first

    const st = GS._state(marker);
    const moves = marker.moves, interval = st.intervalEma, speed = st.speedEma;
    ok(!st.seg, 'nothing may be animating before the duplicates arrive');

    // 20 identical reports over 4 minutes, some claiming 13 knots while parked.
    for (let i = 0; i < 20; i++) {
        advance(12000);
        GS.move(marker, p1, { animate: true, ts: BASE_SEC + 5 + (i + 1) * 12, speed: 13.5 });
    }
    frames(60, 16.7);

    ok(marker.moves === moves, 'duplicates must not move the marker again');
    ok(st.intervalEma === interval && st.speedEma === speed, 'duplicates must not pollute the learned cadence/speed');
    ok(near(meters(marker.getLatLng(), p1), 0, 3), 'marker must stay parked');
});

t('a 2 km jump on a different track snaps once instead of gliding', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.1935611, lng: 73.2353494 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });
    const p1 = project(p0, 10, 100);
    advance(5000);
    GS.move(marker, p1, { animate: true, ts: BASE_SEC + 5 });
    frames(30, 16.7);

    const teleport = { lat: 34.1743650, lng: 73.2331233 };   // 2 km south, other track
    advance(1000);
    GS.move(marker, teleport, { animate: true, ts: BASE_SEC + 6 });

    ok(near(meters(marker.getLatLng(), teleport), 0, 0.01), 'teleport must snap instantly');
    ok(!GS._state(marker).seg, 'no glide may be left running after a teleport snap');
});

t('device silent >5 min snaps once on reconnect, then glides normally', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.2251766, lng: 73.2434816 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });
    for (let i = 0; i < 4; i++) {                       // build a 5 s cadence
        const p = project(p0, 10, 40 * (i + 1));
        advance(5000);
        GS.move(marker, p, { animate: true, ts: BASE_SEC + 5 * (i + 1) });
        frames(60, 16.7);
    }

    const parked = marker.getLatLng();
    const wake = project(parked, 10, 300);
    advance(400000);                                    // 6.6 minutes of silence
    GS.move(marker, wake, { animate: true, ts: BASE_SEC + 20 + 400 });
    ok(near(meters(marker.getLatLng(), wake), 0, 0.01), 'reconnect must snap to the newest fix');

    const next = project(wake, 10, 150);                // and after that it glides again
    advance(5000);
    GS.move(marker, next, { animate: true, ts: BASE_SEC + 425 });
    ok(!!GS._state(marker).seg, 'glide must resume after the reconnect snap');
});

t('a long gap (45 s) is bridged with one adapated glide, not a burst of jumps', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.2251766, lng: 73.2434816 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });
    for (let i = 0; i < 5; i++) {                       // 4 s cadence
        const p = project(p0, 10, 30 * (i + 1));
        advance(4000);
        GS.move(marker, p, { animate: true, ts: BASE_SEC + 4 * (i + 1) });
        frames(40, 16.7);
    }

    const p1 = project(p0, 12, 600);                    // 45 s later
    advance(45000);
    GS.move(marker, p1, { animate: true, ts: BASE_SEC + 20 + 45 });
    const st = GS._state(marker);
    ok(st.seg, 'gap must produce a glide');
    ok(st.seg.dur >= 3000, 'glide must be stretched for a long gap, got ' + st.seg.dur);

    const p2 = project(p1, 12, 40);
    advance(1000);
    GS.move(marker, p2, { animate: true, ts: BASE_SEC + 66 });
    ok(!!GS._state(marker).seg, 'the catch-up fix must retarget the glide');
});

// ============================================== continuity / mid-glide retarget
section('Continuous redirection (no restart, no jitter on mid-glide updates)');

t('a fix arriving mid-glide keeps the position continuous', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.2, lng: 73.2 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });

    const target = project(p0, 0, 250);                 // 250 m in 5 s = 180 km/h
    advance(5000);
    GS.move(marker, target, { animate: true, ts: BASE_SEC + 5 });

    frames(120, 16.7);                                  // ~2 s into the glide
    const before = marker.getLatLng();

    const newTarget = project(before, 30, 150);         // 150 m in 5 s = 108 km/h
    advance(5000);
    GS.move(marker, newTarget, { animate: true, ts: BASE_SEC + 10 });
    const after = marker.getLatLng();

    ok(meters(before, after) < 2, 'retarget must not move the marker: jumped ' + meters(before, after).toFixed(2) + ' m');

    let maxStep = 0, prev = after;
    for (let i = 0; i < 400; i++) {
        frame(16.7);
        maxStep = Math.max(maxStep, meters(prev, marker.getLatLng()));
        prev = marker.getLatLng();
    }
    ok(maxStep < 8, 'no jitter: biggest single-frame step was ' + maxStep.toFixed(2) + ' m');
    ok(near(meters(marker.getLatLng(), newTarget), 0, 3), 'marker must reach the newest target');
});

t('out-of-order (stale) backlog fixes are dropped, the newest one wins', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.2, lng: 73.2 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC + 100 });
    const p1 = project(p0, 0, 100);
    advance(5000);
    GS.move(marker, p1, { animate: true, ts: BASE_SEC + 105 });

    const stale = project(p0, 180, 100);                // older timestamp, wrong place
    GS.move(marker, stale, { animate: true, ts: BASE_SEC + 101 });
    ok(near(meters(GS._state(marker).target, p1), 0, 0.01), 'stale fix must not become the target');
});

// =============================================================== degradation
section('Graceful degradation');

t('hidden tab: pause + snap when it comes back', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.2, lng: 73.2 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });
    const p1 = project(p0, 0, 200);
    advance(5000);
    GS.move(marker, p1, { animate: true, ts: BASE_SEC + 5 });
    ok(!!GS._state(marker).seg, 'glide running');

    global.document.hidden = true;
    const away = project(p1, 0, 900);
    advance(20000);
    GS.move(marker, away, { animate: true, ts: BASE_SEC + 25 });
    ok(near(meters(marker.getLatLng(), away), 0, 0.01), 'hidden tab must snap to the latest fix');

    global.document.hidden = false;
    ok(typeof docHandlers.visibilitychange === 'function', 'engine must listen for visibilitychange');
    docHandlers.visibilitychange();
    const p2 = project(away, 0, 100);
    advance(5000);
    GS.move(marker, p2, { animate: true, ts: BASE_SEC + 30 });
    ok(!!GS._state(marker).seg, 'animation must resume after the tab comes back');
});

t('animation setting off: instant snap and any running glide is cancelled', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.2, lng: 73.2 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });
    const p1 = project(p0, 0, 200);
    advance(5000);
    GS.move(marker, p1, { animate: true, ts: BASE_SEC + 5 });
    ok(!!GS._state(marker).seg, 'glide running');

    const p2 = project(p1, 0, 200);
    advance(5000);
    GS.move(marker, p2, { animate: false, ts: BASE_SEC + 10 });
    ok(!GS._state(marker).seg, 'glide must be cancelled');
    ok(near(meters(marker.getLatLng(), p2), 0, 0.01), 'must snap instantly');
});

t('GpsSmooth.setEnabled(false) stops everything', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.2, lng: 73.2 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });
    const p1 = project(p0, 0, 200);
    advance(5000);
    GS.move(marker, p1, { animate: true, ts: BASE_SEC + 5 });

    GS.setEnabled(false);
    ok(GS._active().length === 0, 'no active animations after disabling');
    ok(near(meters(marker.getLatLng(), p1), 0, 2), 'marker must be parked on its last target');
    GS.setEnabled(true);
});

// ============================================================ online/offline
section('Offline devices');

t('a silent device parks exactly on its last known fix and stops', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.2647894, lng: 73.2361511 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });
    const p1 = project(p0, 0, 60);
    advance(5000);
    GS.move(marker, p1, { animate: true, ts: BASE_SEC + 5 });

    frames(600, 16.7);                                  // reach the fix
    ok(near(meters(marker.getLatLng(), p1), 0, 0.01), 'the marker must land exactly on the fix');

    const moves = marker.moves;
    frames(60 * 90, 16.7);                              // 90 s of silence
    ok(marker.moves === moves, 'a silent device must stop animating entirely, not creep');
    ok(near(meters(marker.getLatLng(), p1), 0, 0.01), 'the marker must stay exactly on the last fix');
});

t('optional coasting stays inside its cap when explicitly enabled', () => {
    const { marker } = fresh();
    CFG.coast = true;
    const p0 = { lat: 34.2, lng: 73.2 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });
    const p1 = project(p0, 0, 300);
    advance(5000);
    GS.move(marker, p1, { animate: true, ts: BASE_SEC + 5 });

    frames(60 * 12, 16.7);
    const creep = meters(marker.getLatLng(), p1);
    ok(creep <= CFG.coastMaxMeters + 1, 'coast must respect coastMaxMeters, got ' + creep.toFixed(1));
    ok(creep <= 300 * CFG.coastSegRatio + 1, 'coast must respect coastSegRatio');
    CFG.coast = false;
});

t('coasting between fixes never exceeds its (small) cap', () => {
    const { marker } = fresh();
    const p0 = { lat: 34.2, lng: 73.2 };
    GS.move(marker, p0, { animate: true, ts: BASE_SEC });
    const p1 = project(p0, 0, 300);
    advance(5000);
    GS.move(marker, p1, { animate: true, ts: BASE_SEC + 5 });

    frames(60 * 12, 16.7);                              // glide done + 12 s of silence
    const creep = meters(marker.getLatLng(), p1);
    ok(creep <= CFG.coastMaxMeters + 1, 'coast must stay inside its cap, got ' + creep.toFixed(1));
    ok(creep <= 300 * CFG.coastSegRatio + 1, 'coast must stay inside 20% of the last segment');
});

// ================================================================== follow mode
section('Follow mode');

// Keeps the fake map centered on a position, like a real Leaflet viewport would be.
function centerOn(map, ll) { map.centerWorld = map.worldPx(ll); }

t('follow pans fractionally towards the marker and never setView', () => {
    const { map, marker } = fresh();
    const p0 = { lat: 34.2, lng: 73.2 };
    marker._latlng = { lat: p0.lat, lng: p0.lng };
    centerOn(map, p0);
    ok(GS.follow(marker, map) === true, 'follow must start');
    ok(GS.isFollowing(), 'state must report following');
    ok(map.panToCalls.length === 1, 'follow must do one animated panTo on start');
    ok(map.panToCalls[0].opts.animate === true, 'the initial centering pan must be animated');

    // Push the marker outside the dead zone (400 px east) and let follow chase it.
    const dx = 400 / map.scale;                         // 400 world px east
    const p1 = { lat: p0.lat, lng: p0.lng + dx };
    GS.move(marker, p1, { animate: false });
    marker.setLatLngs.length = 0;

    let maxPan = 0;
    for (let i = 0; i < 200; i++) {
        frame(16.7);
        map.pans.forEach((p) => { maxPan = Math.max(maxPan, Math.abs(p.dx), Math.abs(p.dy)); });
        map.pans.length = 0;
    }
    ok(maxPan > 0, 'follow must pan the map');
    ok(maxPan <= CFG.follow.maxPanPerFrame + 1, 'pan steps must be tiny and eased, biggest=' + maxPan);
    ok(map.setViewCalls.length === 0, 'follow must never call setView');

    const pt = map.latLngToContainerPoint(marker.getLatLng());
    const size = map.getSize();
    const dzx = size.x * CFG.follow.deadZoneX, dzy = size.y * CFG.follow.deadZoneY;
    ok(pt.x >= dzx - 2 && pt.x <= size.x - dzx + 2, 'marker must be pulled back inside the dead zone (x=' + pt.x.toFixed(0) + ')');
    ok(pt.y >= dzy - 2 && pt.y <= size.y - dzy + 2, 'marker must be pulled back inside the dead zone (y=' + pt.y.toFixed(0) + ')');

    map.pans.length = 0;                                // settled: follow must idle
    frames(120, 16.7);
    ok(map.pans.length === 0, 'follow must not oscillate once the marker is back inside the dead zone');
});

t('the map keeps up with a moving marker (no lag behind the vehicle)', () => {
    const { map, marker } = fresh();
    const p0 = { lat: 34.2, lng: 73.2 };
    marker._latlng = { lat: p0.lat, lng: p0.lng };
    centerOn(map, p0);
    GS.follow(marker, map);

    // 36 km/h = 10 m/s, sampled every 5 s, animated by the engine.
    const speed = 10, step = speed * 5;
    const size = map.getSize();
    const dzx = size.x * CFG.follow.deadZoneX, dzy = size.y * CFG.follow.deadZoneY;
    let p = p0, worst = 0, frames_run = 0;

    for (let i = 1; i <= 12; i++) {
        p = project(p0, 45, step * i);
        advance(5000);
        GS.move(marker, p, { animate: true, ts: BASE_SEC + i * 5 });
        for (let f = 0; f < 299; f++) {                 // 299 frames ~ 5 s of driving
            frame(16.7);
            frames_run++;
            if (frames_run < 60) continue;              // ignore the initial catch-up
            const pt = map.latLngToContainerPoint(marker.getLatLng());
            const off = Math.max(dzx - pt.x, pt.x - (size.x - dzx), dzy - pt.y, pt.y - (size.y - dzy));
            worst = Math.max(worst, off);
        }
    }
    ok(frames_run > 3000, 'the test must run a realistic number of frames');
    ok(worst < 6, 'the map must stay within a few pixels of the dead-zone edge while driving (worst=' + worst.toFixed(1) + 'px)');
    ok(near(meters(marker.getLatLng(), p), 0, 15), 'the marker itself must keep up with the fixes');
});

t('user drag pauses follow and the toggle can resume it', () => {
    const { map, marker } = fresh();
    marker._latlng = { lat: 34.2, lng: 73.2 };
    let notified = [];
    GS.onFollowChange((on) => notified.push(on));
    GS.follow(marker, map);
    ok(notified[notified.length - 1] === true, 'follow change callback must report ON');

    map.fire('dragstart');
    ok(!GS.isFollowing(), 'dragging the map must pause follow');
    ok(notified[notified.length - 1] === false, 'callback must report the pause to the UI');

    map.pans.length = 0;
    const p1 = { lat: 34.2, lng: 73.3 };
    marker.setLatLng(p1);
    frames(120, 16.7);
    ok(map.pans.length === 0, 'paused follow must not fight the user');

    GS.resumeFollow();
    ok(GS.isFollowing(), 'resumeFollow must re-enable it');
    frames(120, 16.7);
    ok(map.pans.length > 0, 'follow must pan again after resuming');

    GS.unfollow();
    ok(!GS.isFollowing(), 'unfollow must stop it');
    frame(16.7);                                        // let the loop notice
    ok(pending === null, 'the rAF loop must stop when nothing is animated or followed');
});

t('follow stops itself when the followed marker leaves the map', () => {
    const { map, marker } = fresh();
    marker._latlng = { lat: 34.2, lng: 73.2 };
    GS.follow(marker, map);
    marker._map = null;                                  // cluster removed it
    frame(16.7);
    frame(16.7);
    ok(!GS.isFollowing(), 'follow must stop when the layer is gone');
});

// ==================================================================== perf
section('Performance / concurrency');

t('per-frame cost stays inside the frame budget with many markers', () => {
    GS._reset();
    pending = null;
    const map = makeMap();
    const N = 120;
    const markers = [];
    // Same as a real poll: every device is placed, then one poll updates them
    // all at the same moment, and the glide lasts one poll interval (5 s).
    for (let i = 0; i < N; i++) {
        const lat = 34 + i * 0.0001, lng = 73 + i * 0.0001;
        const m = makeMarker(map, lat, lng, i);
        GS.move(m, { lat: lat, lng: lng }, { animate: true, ts: BASE_SEC });
        markers.push(m);
    }
    advance(5000);
    for (let i = 0; i < N; i++) {
        const base = markers[i].getLatLng();
        GS.move(markers[i], { lat: base.lat + 0.0008, lng: base.lng + 0.0008 }, { animate: true, ts: BASE_SEC + 5 });
    }
    const animating = GS._active().length;
    const movesBefore = markers.reduce((a, m) => a + m.moves, 0);
    const started = process.hrtime.bigint();
    frames(60, 16.7);                                   // 1 s with everything moving
    const perFrame = Number(process.hrtime.bigint() - started) / 1e6 / 60;
    const movesDuring = markers.reduce((a, m) => a + m.moves, 0) - movesBefore;
    ok(movesDuring >= N * 55, 'every marker must have moved on nearly every frame (got ' + movesDuring + ' moves)');
    const perMarker = perFrame / N;
    const budget = 16.7;                                 // 60 fps
    const headroom = Math.floor(budget / Math.max(perMarker, 1e-6));

    console.log('      engine cost: ' + perFrame.toFixed(3) + ' ms/frame for ' + N +
        ' animated markers (' + (perMarker * 1000).toFixed(1) + ' \u00b5s each)');
    console.log('      => engine math alone supports ~' + headroom + ' markers inside a 16.7 ms frame');
    ok(perMarker < 0.05, 'per-marker cost must stay far below the frame budget');
    ok(animating === N, 'all markers must be animated, got ' + animating);
});

// ==================================================================== results
console.log('\n' + '='.repeat(64));
console.log('passed: ' + passed + '   failed: ' + failed);
if (failed) {
    console.log('\nfailures:');
    failures.forEach((f) => console.log('  - ' + f));
    process.exit(1);
}
console.log('ALL GPS-SMOOTH CHECKS PASSED');

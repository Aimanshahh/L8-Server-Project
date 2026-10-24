'use strict';

const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const path = require('path');

const root = path.join(__dirname, '..', '..');
const enginePath = path.join(root, 'public', 'assets', 'js', 'gps-glide.js');
const appPath = path.join(root, 'public', 'assets', 'js', 'app.js');

let checks = 0;
function ok(condition, message) {
    checks++;
    assert.ok(condition, message);
    console.log('  ok - ' + message);
}

let clock = 0;
let rafCb = null;

const sandbox = {
    console,
    Date,
    Math,
    WeakMap,
    isFinite,
    parseFloat,
    setTimeout,
    clearTimeout,
    performance: { now: function () { return clock; } },
    requestAnimationFrame: function (fn) { rafCb = fn; return 1; },
    localStorage: {
        getItem: function () { return null; },
        setItem: function () {}
    },
    document: {
        hidden: false,
        addEventListener: function () {}
    },
    app: null
};

sandbox.window = sandbox;
vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(enginePath, 'utf8'), sandbox, { filename: 'gps-glide.js' });

const G = sandbox.GpsGlide;
G.cfg.now = function () { return clock; };
G.cfg.road.enabled = false;

function advance(ms) {
    const step = 16;
    while (ms > 0) {
        const delta = Math.min(step, ms);
        clock += delta;
        ms -= delta;

        if (rafCb) {
            const cb = rafCb;
            rafCb = null;
            cb(clock);
        }
    }
}

function makeLayer(id) {
    const layer = {
        options: { device_id: id },
        _map: {},
        ll: null,
        setLatLng: function (p) { this.ll = { lat: p.lat, lng: p.lng }; },
        getLatLng: function () { return this.ll; },
        setIconAngle: function (a) { this.options.iconAngle = a; }
    };
    return layer;
}

const base = Date.now() - 300000;
const LAT = 24.9621;
const LNG = 67.1984;
const NORTH_40M = 0.00036;
const NORTH_20M = 0.00018;

function fix(id, seconds, lat, lng, speed, course) {
    return {
        id: id,
        t: base + seconds * 1000,
        lat: lat,
        lng: lng,
        sp: speed === null ? null : speed,
        co: course === null ? null : course,
        v: 1
    };
}

function approx(actual, expected, eps, message) {
    ok(Math.abs(actual - expected) <= eps, message + ' (' + actual + ' ~ ' + expected + ')');
}

console.log('\n1. initial placement');
const l1 = makeLayer(1);
const p0 = fix(1, 0, LAT, LNG, 20, 90);
G.move(l1, p0, { ts: p0.t / 1000, track: [p0], positionId: 1, speed: 20, course: 90, rotate: true });
approx(l1.ll.lat, LAT, 1e-9, 'marker is placed at the first fix');
approx(l1.options.iconAngle, 90, 0.01, 'initial course is applied');
ok(G.activeCount() === 0, 'first placement does not start an animation');

console.log('\n2. straight movement north, heading follows the path');
const p1 = fix(2, 5, LAT + NORTH_40M, LNG, 20, 0);
G.move(l1, p1, { ts: p1.t / 1000, track: [p0, p1], positionId: 2, speed: 20, course: 0, rotate: true });
ok(G.activeCount() === 1, 'moving fix starts one animation');

advance(250);
ok(l1.options.iconAngle < 90 && l1.options.iconAngle > 0, 'heading rotates towards the direction of travel');
advance(2250);
const midLat = l1.ll.lat;
ok(midLat > LAT + 0.00010 && midLat < LAT + 0.00030, 'midpoint is between the two real fixes');

advance(3200);
approx(l1.ll.lat, LAT + NORTH_40M, 1e-8, 'animation ends exactly on the newest fix');
approx(l1.ll.lng, LNG, 1e-9, 'longitude is unchanged');
approx(l1.options.iconAngle, 0, 0.5, 'final heading points north');

const finishedPos = { lat: l1.ll.lat, lng: l1.ll.lng };
advance(10000);
approx(l1.ll.lat, finishedPos.lat, 1e-9, 'no projected movement after the last fix');
ok(G.activeCount() === 0, 'animation stops when the real fixes are exhausted');

console.log('\n3. stale/out-of-order packet');
const stale = fix(1, 4, LAT + 0.0001, LNG, 20, 90);
G.move(l1, stale, { ts: stale.t / 1000, track: [p0, stale], positionId: 1, speed: 20, course: 90 });
approx(l1.ll.lat, finishedPos.lat, 1e-9, 'older fix cannot move the marker backwards');

console.log('\n4. duplicate / stationary update');
const stopped = fix(3, 10, LAT + NORTH_40M, LNG, 0, 180);
G.move(l1, stopped, { ts: stopped.t / 1000, track: [p1, stopped], positionId: 3, speed: 0, course: 180 });
approx(l1.ll.lat, finishedPos.lat, 1e-9, 'duplicate coordinate does not move');
approx(l1.options.iconAngle, 0, 0.5, 'stationary vehicle keeps its last heading');

console.log('\n5. genuine reversal');
const reverse = fix(4, 15, LAT + NORTH_40M - NORTH_20M, LNG, 10, 180);
G.move(l1, reverse, {
    ts: reverse.t / 1000,
    track: [p1, stopped, reverse],
    positionId: 4,
    speed: 10,
    course: 180,
    rotate: true
});
advance(5500);
approx(l1.ll.lat, LAT + NORTH_40M - NORTH_20M, 1e-8, 'newer reversed fix is reached');
approx(l1.options.iconAngle, 180, 0.5, 'heading follows the actual reverse direction');

console.log('\n6. impossible GPS jump');
const jump = fix(5, 20, LAT + 0.045, LNG, 10, 0);
G.move(l1, jump, { ts: jump.t / 1000, track: [reverse, jump], positionId: 5, speed: 10, course: 0 });
approx(l1.ll.lat, LAT + 0.045, 1e-8, 'jump snaps to the newest real coordinate');
ok(G.activeCount() === 0, 'jump is not animated through the map');
approx(l1.options.iconAngle, 180, 0.5, 'teleport does not spin the vehicle');

console.log('\n7. retarget while an animation is running');
const l2 = makeLayer(2);
const q0 = fix(20, 0, LAT, LNG, 20, 0);
const q1 = fix(21, 5, LAT + NORTH_40M, LNG, 20, 0);
G.move(l2, q0, { ts: q0.t / 1000, track: [q0], positionId: 20, speed: 20, course: 0 });
G.move(l2, q1, { ts: q1.t / 1000, track: [q0, q1], positionId: 21, speed: 20, course: 0 });
advance(1000);
const beforeRetarget = { lat: l2.ll.lat, lng: l2.ll.lng };
const q2 = fix(22, 10, LAT + NORTH_40M * 2, LNG, 20, 0);
G.move(l2, q2, { ts: q2.t / 1000, track: [q0, q1, q2], positionId: 22, speed: 20, course: 0 });
advance(16);
const afterRetarget = { lat: l2.ll.lat, lng: l2.ll.lng };
const stepMeters = Math.hypot((afterRetarget.lat - beforeRetarget.lat) * 111000,
    (afterRetarget.lng - beforeRetarget.lng) * 111000 * Math.cos(LAT * Math.PI / 180));
ok(stepMeters < 2, 'retarget does not jump at the first frame (' + stepMeters.toFixed(2) + ' m)');
ok(G.activeCount() === 1, 'only one animation remains active');
advance(8000);
approx(l2.ll.lat, LAT + NORTH_40M * 2, 1e-8, 'retarget finishes on the newest fix');

console.log('\n7b. left/right turn retarget keeps the corner');
const l5 = makeLayer(5);
const c0 = fix(50, 0, LAT, LNG, 20, 0);
const c1 = fix(51, 5, LAT + NORTH_40M, LNG, 20, 90);
const c2 = fix(52, 10, LAT + NORTH_40M, LNG + NORTH_40M, 20, 90);
G.move(l5, c0, { ts: c0.t / 1000, track: [c0], positionId: 50, speed: 20, course: 0, rotate: true });
G.move(l5, c1, { ts: c1.t / 1000, track: [c0, c1], positionId: 51, speed: 20, course: 0, rotate: true });
advance(2500);
G.move(l5, c2, { ts: c2.t / 1000, track: [c0, c1, c2], positionId: 52, speed: 20, course: 90, rotate: true });
advance(2000);
ok(l5.ll.lat > LAT + NORTH_40M * 0.7, 'retarget follows the north leg instead of cutting diagonally');
ok(l5.ll.lng > LNG + 0.00010, 'retarget then reaches the east leg');
advance(4000);
approx(l5.ll.lat, LAT + NORTH_40M, 1e-8, 'turn ends on the corner fix');
approx(l5.ll.lng, LNG + NORTH_40M, 1e-8, 'turn ends on the newest fix');
approx(l5.options.iconAngle, 90, 1, 'turn heading points east');

console.log('\n8. stationary jitter');
const l3 = makeLayer(3);
const j0 = fix(30, 0, LAT, LNG, 0, 90);
G.move(l3, j0, { ts: j0.t / 1000, track: [j0], positionId: 30, speed: 0, course: 90 });
advance(200);
for (let i = 1; i <= 4; i++) {
    const jitter = fix(30 + i, i * 5, LAT + (i % 2 ? 0.00003 : 0), LNG, 0, i % 2 ? 90 : 270);
    G.move(l3, jitter, {
        ts: jitter.t / 1000,
        track: [j0, jitter],
        positionId: 30 + i,
        speed: 0,
        course: i % 2 ? 90 : 270
    });
    advance(200);
}
approx(l3.ll.lat, LAT, 1e-9, 'a parked vehicle is not moved by GPS jitter');
approx(l3.options.iconAngle, 90, 0.5, 'parked heading does not rotate randomly');

console.log('\n9. low-accuracy fix is not drawn');
const l4 = makeLayer(4);
const a0 = fix(40, 0, LAT, LNG, 30, 0);
G.move(l4, a0, { ts: a0.t / 1000, track: [a0], positionId: 40, speed: 30, course: 0, accuracy: 5 });
const a1 = fix(41, 5, LAT + 0.0009, LNG, 30, 0);
G.move(l4, a1, {
    ts: a1.t / 1000,
    track: [a0, a1],
    positionId: 41,
    speed: 30,
    course: 0,
    accuracy: 300,
    rotate: true
});
advance(1000);
approx(l4.ll.lat, LAT, 1e-9, 'low-accuracy jump leaves the marker at the last good fix');
approx(l4.options.iconAngle, 0, 0.5, 'low-accuracy jump does not change the heading');

console.log('\n10. track normalisation');
const track = G._normaliseTrack([
    { id: 3, t: base + 30000, lat: LAT, lng: LNG, v: 0 },
    { id: 2, t: base + 20000, lat: LAT, lng: LNG, sp: 10, co: 350, v: 1 },
    { id: 1, t: base + 10000, lat: LAT + 0.01, lng: LNG, sp: 10, co: 10, v: 1 },
    { id: 9, t: base + 20000, lat: LAT + 0.02, lng: LNG, sp: 10, co: 45, v: 1 }
]);
ok(track.length === 2, 'invalid and same-time burst rows are removed');
ok(track[0].id === 1 && track[1].id === 9, 'track is chronological and keeps the newest burst row');
ok(track[1].sp === 10 && track[1].co === 45, 'speed/course are preserved on the kept row');

console.log('\n11. road match validation');
function encodePolyline(points) {
    let lastLat = 0, lastLng = 0, out = '';
    function enc(v) {
        v = v < 0 ? ~(v << 1) : (v << 1);
        let s = '';
        while (v >= 0x20) {
            s += String.fromCharCode((0x20 | (v & 0x1f)) + 63);
            v >>= 5;
        }
        return s + String.fromCharCode(v + 63);
    }
    points.forEach(function (p) {
        const lat = Math.round(p.lat * 1e5);
        const lng = Math.round(p.lng * 1e5);
        out += enc(lat - lastLat) + enc(lng - lastLng);
        lastLat = lat;
        lastLng = lng;
    });
    return out;
}

const a = { lat: LAT, lng: LNG };
const b = { lat: LAT + NORTH_40M, lng: LNG };
const good = {
    code: 'Ok',
    matchings: [{ geometry: encodePolyline([a, b]), confidence: 0.95 }],
    tracepoints: [
        { location: [LNG, LAT] },
        { location: [LNG, LAT + NORTH_40M] }
    ]
};
const matched = G._validateMatch(good, [a, b]);
ok(matched && matched.length === 2, 'trustworthy match is accepted');

const badSnap = JSON.parse(JSON.stringify(good));
badSnap.tracepoints[0].location = [LNG + 0.001, LAT];
ok(G._validateMatch(badSnap, [a, b]) === null, 'match outside the snap radius is rejected');

const badDetour = JSON.parse(JSON.stringify(good));
badDetour.matchings[0].geometry = encodePolyline([
    a,
    { lat: LAT + 0.005, lng: LNG },
    { lat: LAT + 0.005, lng: LNG + 0.01 },
    b
]);
ok(G._validateMatch(badDetour, [a, b]) === null, 'implausible detour is rejected');

console.log('\n12. wiring and source guards');
const app = fs.readFileSync(appPath, 'utf8');
const engine = fs.readFileSync(enginePath, 'utf8');
ok(app.indexOf('track: options.track') >= 0, 'device update passes the real track to the engine');
ok(app.indexOf('options.fix_timestamp || options.timestamp') >= 0, 'fix time is preferred over server arrival time');
ok(app.indexOf('GpsGlide.forget(layer)') >= 0, 'removed markers release their animation state');
ok(app.indexOf('op-follow') < 0, 'duplicate follow loops were removed');
ok(engine.indexOf('projMax') < 0 && engine.indexOf('projecting') < 0, 'unbounded projection code was removed');
ok(engine.indexOf('setHeading(st, b, false)') >= 0, 'per-frame heading is derived from the path');
ok(engine.indexOf('plausibleFixSequence') >= 0, 'implausible jumps are rejected before animation');
ok(app.indexOf('isFinite(_lat)') >= 0 && app.indexOf('isFinite(_lng)') >= 0,
    'unusable coordinates are rejected before L.LatLng can throw');
ok(app.indexOf('updateLayer failed for device') >= 0,
    'one failing device cannot abort the whole object screen');
ok(app.indexOf('_bounds.push( _itemLL )') >= 0, 'fitBounds skips devices without a layer');

console.log('\nALL GPS-GLIDE CHECKS PASSED (' + checks + ' checks)');

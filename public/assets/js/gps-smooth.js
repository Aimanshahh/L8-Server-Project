/*!
 * GpsSmooth v2.0 - continuous, pace-matched marker glide engine for GPS trackers
 * ---------------------------------------------------------------------------
 * Drop-in replacement for Leaflet.Marker.SlideTo animation on the live map.
 *
 * Why it exists (all four failure modes come straight from real tracker logs):
 *   jumps/teleports    -> distance+speed plausibility rules, backlog detection
 *   stop-start stutter -> per-device EMA cadence + per-device EMA speed drive
 *                         the glide duration, so a device that updates every
 *                         3 minutes glides slowly instead of fast-then-freeze
 *   flying off-road    -> the glide follows a distance-parameterised path and an
 *                         optional OSRM road provider can replace the straight
 *                         line for long segments (blended in, never a pop)
 *   map lagging        -> Follow mode pans the map fractionally every animation
 *                         frame (dead-zone), never map.setView() / fitBounds
 *
 * Hard rules implemented here:
 *   - never CSS-transition a marker (Leaflet owns the transform): we call
 *     marker.setLatLng() once per animation frame from a single rAF loop
 *   - never a hard-coded animation duration: duration = remaining distance /
 *     observed speed, bounded by the observed update cadence
 *   - duplicate coordinates are dropped before they can queue up
 *   - same-timestamp bursts collapse to the newest fix (one glide, not 37)
 *   - out-of-order / stale backlog fixes are dropped (newest fix always wins)
 *   - tab hidden -> animation pauses and snaps to the latest known position
 *   - device silent for >5 min (or 3x its own cadence) -> snap once, then glide
 *   - a single known position -> place it and stop
 *
 * No dependencies, no globals beyond window.GpsSmooth. Plain {lat,lng} objects
 * are used everywhere so the engine can be unit-tested in Node without Leaflet.
 */
(function (win, doc) {
    'use strict';

    var VERSION = '2.0';

    // ------------------------------------------------------------------ config
    var CFG = {
        enabled: true,            // master switch, mirrors app.settings.animateDeviceMove
        debug: false,             // console trace
        trace: true,              // keep a per-device "last decision" for diagnostics

        minSegMs: 700,            // never animate a segment faster than this (anti-jitter)
        maxSegMs: 900000,         // absolute ceiling (15 min) so nothing glides forever
        maxSegFactor: 2.5,        // a segment must not stretch far past the observed cadence
        defaultIntervalMs: 5000,  // fallback cadence before we have learned one (app.checkFrequency)
        intervalEmaAlpha: 0.35,
        speedEmaAlpha: 0.4,

        minMoveMeters: 1.0,       // duplicate / GPS-noise floor
        snapMeters: 5000,         // distance guard when we have no usable time delta
        burstJumpMeters: 1500,    // same-timestamp fix that is this far away => snap
        maxPlausibleSpeed: 70,    // m/s (~250 km/h) - above this it is a teleport
        maxVisualSpeed: 60,       // m/s (~215 km/h) pace ceiling
        minVisualSpeed: 0.35,     // m/s pace floor (a slow crawl must stay visible)

        offlineMs: 300000,        // 5 min: device is considered offline
        offlineFactor: 3,         // ...or 3x its own learned cadence, whichever is larger
        burstWindowMs: 350,       // fixes arriving in the same tick burst are one event
        ease: 0.25,               // 0 = linear speed, 1 = full ease-in-out sine

        // Optional "keep creeping past the last fix" mode. Off by default: a
        // late fix must leave the marker exactly on the last reported position
        // (creeping past it would show the vehicle somewhere it never reported).
        coast: false,
        coastMaxMeters: 25,       // when enabled: never more than this far past
        coastSegRatio: 0.05,      // ...and never more than 5% of the last segment
        coastTauMs: 15000,        // decay time constant
        coastMaxMs: 60000,        // stop coasting after a minute of silence

        follow: {
            deadZoneX: 0.18,      // keep the marker inside the middle 64% of the map
            deadZoneY: 0.18,
            alpha: 0.14,          // fraction of the error corrected per frame
            maxPanPerFrame: 140,  // px, so a teleport does not whip the map
            pauseOnDrag: true
        },

        road: {
            provider: null,       // function(from, to, done) -> done([{lat,lng},...])
            minMeters: 400,       // only ask for long segments
            blendMs: 250,         // blend the road geometry in instead of popping
            cacheSize: 40,
            debug: false
        },

        now: null,                // test hook: function() -> ms
        scheduler: null           // test hook: { request(fn), cancel(id) }
    };

    // ------------------------------------------------------------- math utils
    var EARTH_R = 6371008.8;
    var DEG = Math.PI / 180;

    function distance(a, b) {
        if (!a || !b) return 0;
        var dLat = (b.lat - a.lat) * DEG,
            dLng = (b.lng - a.lng) * DEG,
            la1 = a.lat * DEG,
            la2 = b.lat * DEG,
            h = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(la1) * Math.cos(la2) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return 2 * EARTH_R * Math.asin(Math.min(1, Math.sqrt(h)));
    }

    function mix(a, b, k) {
        return { lat: a.lat + (b.lat - a.lat) * k, lng: a.lng + (b.lng - a.lng) * k };
    }

    function bearing(a, b) {
        var la1 = a.lat * DEG, la2 = b.lat * DEG, dLng = (b.lng - a.lng) * DEG;
        var y = Math.sin(dLng) * Math.cos(la2);
        var x = Math.cos(la1) * Math.sin(la2) - Math.sin(la1) * Math.cos(la2) * Math.cos(dLng);
        return (Math.atan2(y, x) / DEG + 360) % 360;
    }

    function project(a, brg, meters) {
        var d = meters / EARTH_R, t = brg * DEG, la1 = a.lat * DEG, lo1 = a.lng * DEG;
        var sinLa2 = Math.sin(la1) * Math.cos(d) + Math.cos(la1) * Math.sin(d) * Math.cos(t);
        var la2 = Math.asin(Math.min(1, Math.max(-1, sinLa2)));
        var lo2 = lo1 + Math.atan2(Math.sin(t) * Math.sin(d) * Math.cos(la1),
            Math.cos(d) - Math.sin(la1) * sinLa2);
        return { lat: la2 / DEG, lng: ((lo2 / DEG) + 540) % 360 - 180 };
    }

    function clamp(v, lo, hi) { return v < lo ? lo : (v > hi ? hi : v); }
    function ema(prev, value, alpha) { return prev ? prev + alpha * (value - prev) : value; }
    function clone(a) { return { lat: a.lat, lng: a.lng }; }
    function valid(p) { return !!p && typeof p.lat === 'number' && typeof p.lng === 'number' &&
        isFinite(p.lat) && isFinite(p.lng) && Math.abs(p.lat) <= 90 && Math.abs(p.lng) <= 180; }

    function toLatLng(p) {
        if (!p) return null;
        var q = null;
        if (typeof p.lat === 'number' && typeof p.lng === 'number') q = { lat: p.lat, lng: p.lng };
        else if (typeof p.lat === 'function' && typeof p.lng === 'function') q = { lat: p.lat(), lng: p.lng() };
        else if (p.length === 2) q = { lat: parseFloat(p[0]), lng: parseFloat(p[1]) };
        if (!q || !isFinite(q.lat) || !isFinite(q.lng)) return null;
        return valid(q) ? q : null;
    }

    // Ease blend: keeps constant-speed gliding but softens start/stop corners.
    function easeK(k) {
        var e = clamp(CFG.ease, 0, 1);
        if (e <= 0) return k;
        var s = 0.5 - 0.5 * Math.cos(Math.PI * clamp(k, 0, 1));
        return k + (s - k) * e;
    }

    // ---------------------------------------------------------- path utilities
    function buildPath(points) {
        if (!points || points.length < 2) return null;
        var acc = [0], total = 0, i;
        for (i = 1; i < points.length; i++) {
            total += distance(points[i - 1], points[i]);
            acc.push(total);
        }
        if (!total) return null;
        return { pts: points, acc: acc, total: total };
    }

    function alongPath(path, k) {
        var pts = path.pts;
        var target = clamp(k, 0, 1) * path.total;
        for (var i = 1; i < pts.length; i++) {
            if (path.acc[i] >= target) {
                var len = path.acc[i] - path.acc[i - 1];
                var f = len > 0 ? (target - path.acc[i - 1]) / len : 0;
                return mix(pts[i - 1], pts[i], f);
            }
        }
        return clone(pts[pts.length - 1]);
    }

    function normalisePoints(list) {
        var out = [], i, p;
        if (!list || !list.length) return out;
        for (i = 0; i < list.length; i++) {
            p = toLatLng(list[i]);
            if (p) out.push(p);
        }
        return out;
    }

    // -------------------------------------------------------- time / scheduling
    function now() {
        if (CFG.now) return CFG.now();
        return (win.performance && win.performance.now) ? win.performance.now() : Date.now();
    }

    function requestFrame(fn) {
        if (CFG.scheduler && CFG.scheduler.request) return CFG.scheduler.request(fn);
        if (win.requestAnimationFrame) return win.requestAnimationFrame(fn);
        return win.setTimeout(function () { fn(now()); }, 16);
    }

    function isHidden() {
        return !!(doc && doc.hidden);
    }

    // Normalise a device fix timestamp to milliseconds.
    function normaliseTs(ts) {
        var v = typeof ts === 'string' ? parseInt(ts, 10) : ts;
        if (typeof v !== 'number' || !isFinite(v) || v <= 0) return 0;
        if (v < 1e11) v = v * 1000;                 // seconds -> ms (trackers report seconds)
        if (v < 1e11) return 0;                     // still nonsense
        if (v > Date.now() + 86400000) return 0;    // more than a day in the future -> ignore
        return v;
    }

    // ------------------------------------------------------------------- state
    var active = [];      // animated marker states
    var follow = null;    // { layer, map, userPaused }
    var rafId = null;
    var followCb = null;
    var diagnostics = {}; // layerId -> last decision (ring of 1 per device)

    function stateOf(layer) {
        var st = layer.__gpsSmooth;
        if (st) return st;
        st = layer.__gpsSmooth = {
            layer: layer,
            cur: null,            // current visual position
            target: null,         // newest accepted target
            seg: null,            // { from, to, t0, dur, path, pathAt }
            lastFixTs: 0,         // device fix time (ms)
            lastFixWall: 0,       // wall clock of the last accepted fix
            lastArrivalWall: 0,   // wall clock of the last *call* (burst detection)
            intervalEma: 0,       // learned update cadence (ms)
            speedEma: 0,          // learned ground speed (m/s)
            lastSegLen: 0,
            lastBearing: null,
            segEndedAt: 0,        // wall clock when the last segment finished
            coastFrom: null,
            coastCap: 0,
            frames: 0,
            snaps: 0,
            moves: 0,
            reason: 'init',
            version: VERSION
        };
        return st;
    }

    function note(st, reason, extra) {
        if (CFG.trace) {
            st.reason = reason;
            st.reasonAt = Date.now();
            diagnostics[st.layer.options && st.layer.options.device_id ? st.layer.options.device_id : 'anon:' + (st.layer._leaflet_id || '?')] = {
                reason: reason, at: st.reasonAt, extra: extra || null,
                interval: Math.round(st.intervalEma), speed: Math.round(st.speedEma * 10) / 10
            };
        }
        if (CFG.debug && win.console) {
            win.console.log('[GpsSmooth]', reason, st.layer.options && st.layer.options.device_id, extra || '');
        }
    }

    function addActive(st) {
        if (active.indexOf(st) < 0) active.push(st);
        wake();
    }

    function wake() {
        if (rafId === null) rafId = requestFrame(tick);
    }

    // Place the marker instantly and forget any glide in progress.
    function snap(st, p, ts, t) {
        st.seg = null;
        st.cur = clone(p);
        st.target = clone(p);
        st.coastFrom = null;
        st.coastCap = 0;
        st.segEndedAt = 0;
        if (ts) st.lastFixTs = ts;
        if (t) st.lastFixWall = t;
        st.snaps++;
        if (typeof st.layer.setLatLng === 'function') st.layer.setLatLng({ lat: st.cur.lat, lng: st.cur.lng });
        return st;
    }

    // ----------------------------------------------------------- pace learning
    function suggestedDuration(st, meters, t) {
        var interval = st.intervalEma || CFG.defaultIntervalMs;
        var speed = st.speedEma;

        var dur;
        if (speed && speed > 0) {
            dur = meters / speed * 1000;                     // real pace, learned per device
        } else {
            dur = interval;                                  // no pace yet: play it over one cadence
        }

        // never move faster than the visual ceiling, never a silly crawl
        dur = Math.max(dur, meters / CFG.maxVisualSpeed * 1000);
        if (meters > CFG.minMoveMeters) dur = Math.min(dur, meters / CFG.minVisualSpeed * 1000);

        dur = clamp(dur, CFG.minSegMs, CFG.maxSegMs);
        dur = Math.min(dur, Math.max(CFG.minSegMs, interval * CFG.maxSegFactor));
        return dur;
    }

    // (Re)start the glide towards p, keeping the current position continuous.
    function startSegment(st, p, t, opts) {
        var from = st.cur ? clone(st.cur) : clone(p);
        var meters = distance(from, p);
        var seg = {
            from: from,
            to: clone(p),
            t0: t,
            dur: suggestedDuration(st, meters, t),
            path: null,
            pathAt: 0
        };

        var points = normalisePoints(opts && opts.path);
        points.unshift(clone(from));
        points.push(clone(p));
        if (points.length > 2) {
            seg.path = buildPath(points);
        }

        st.seg = seg;
        st.target = clone(p);
        st.moves++;

        // Long segment + road provider configured -> glide along the real road.
        if (CFG.road.provider && !seg.path && meters >= CFG.road.minMeters) {
            requestRoadPath(st, seg);
        }
        addActive(st);
        return seg;
    }

    // ---------------------------------------------------------- road providers
    var roadCache = [];       // [{key, points}] newest last
    var roadInFlight = {};

    function roadKey(a, b) {
        return a.lat.toFixed(5) + ',' + a.lng.toFixed(5) + '>' + b.lat.toFixed(5) + ',' + b.lng.toFixed(5);
    }

    function roadCacheGet(key) {
        for (var i = roadCache.length - 1; i >= 0; i--) {
            if (roadCache[i].key === key) return roadCache[i].points;
        }
        return null;
    }

    function roadCacheSet(key, points) {
        roadCache.push({ key: key, points: points });
        while (roadCache.length > CFG.road.cacheSize) roadCache.shift();
    }

    function requestRoadPath(st, seg) {
        var key = roadKey(seg.from, seg.to);
        var cached = roadCacheGet(key);
        if (cached) { applyRoadPath(seg, cached); return; }
        if (roadInFlight[key]) return;
        roadInFlight[key] = true;
        try {
            CFG.road.provider(clone(seg.from), clone(seg.to), function (points) {
                roadInFlight[key] = false;
                var pts = normalisePoints(points);
                if (pts.length < 2) return;
                roadCacheSet(key, pts);
                applyRoadPath(seg, pts);
            });
        } catch (e) {
            roadInFlight[key] = false;
            if (CFG.road.debug && win.console) win.console.warn('[GpsSmooth] road provider failed', e);
        }
    }

    function applyRoadPath(seg, points) {
        if (!seg || st_segDone(seg)) return;
        var pts = [clone(seg.from)].concat(points, [clone(seg.to)]);
        var path = buildPath(pts);
        if (!path) return;
        seg.path = path;
        seg.pathAt = now();      // blended in, so the correction never pops
    }

    function st_segDone(seg) { return !!seg.__done; }

    // --------------------------------------------------------------- the glides
    function move(layer, position, opts) {
        opts = opts || {};
        if (!layer || typeof layer.setLatLng !== 'function' || !position) return null;
        var p = toLatLng(position);
        if (!p) return null;

        var animate = opts.animate !== false && CFG.enabled;
        var t = now();
        var st = layer.__gpsSmooth;

        // First time we ever see this marker: place it and learn nothing yet.
        if (!st) {
            st = stateOf(layer);
            var g = (typeof layer.getLatLng === 'function') ? toLatLng(layer.getLatLng()) : null;
            st.cur = g || clone(p);
            st.lastArrivalWall = t;
            if (g && distance(g, p) < CFG.minMoveMeters) {
                st.target = clone(p);
                st.lastFixWall = t;
                st.lastFixTs = normaliseTs(opts.ts) || 0;
                note(st, 'seed');
                return st;
            }
            snap(st, p, normaliseTs(opts.ts), t);
            note(st, 'first-placement');
            return st;
        }

        if (!animate) {
            // Animation disabled in settings -> hard snap, kill anything running.
            var ts0 = normaliseTs(opts.ts);
            snap(st, p, ts0, t);
            note(st, 'snap:animation-off');
            return st;
        }

        var dist = distance(st.target || st.cur, p);
        // Duplicate coordinates / GPS jitter: nothing to animate. Deliberately
        // does not touch the cadence EMA, so a device that repeats the same
        // point for minutes cannot poison the learned interval.
        if (st.target && dist < CFG.minMoveMeters) {
            note(st, 'skip:duplicate', dist.toFixed(2));
            return st;
        }

        if (isHidden()) {
            snap(st, p, normaliseTs(opts.ts), t);
            note(st, 'snap:tab-hidden');
            return st;
        }

        var ts = normaliseTs(opts.ts);
        var dt = (ts && st.lastFixTs) ? (ts - st.lastFixTs) : 0;
        var wallGap = st.lastFixWall ? (t - st.lastFixWall) : 0;

        // Out-of-order backlog: the newest fix always wins. A device clock that
        // jumps far backwards is a different problem - re-anchor on the new time.
        if (dt < 0) {
            if (dt < -600000) {
                snap(st, p, ts, t);
                note(st, 'snap:clock-jump', dt);
            } else {
                note(st, 'drop:stale-fix', dt);
            }
            return st;
        }

        var sameInstant = !!(ts && st.lastFixTs && dt === 0);
        var burst = sameInstant || (st.lastArrivalWall && (t - st.lastArrivalWall) < CFG.burstWindowMs);
        var implied = (dt > 0) ? dist / (dt / 1000) : Infinity;
        var offlineAfter = Math.max(CFG.offlineMs, st.intervalEma * CFG.offlineFactor);
        var silence = Math.max(wallGap, dt > 0 ? dt : 0);

        // ---- snap decisions (in the order that matters for real logs) --------
        if (silence > offlineAfter) {
            snap(st, p, ts, t);
            note(st, 'snap:offline-reconnect', Math.round(silence));
            return st;
        }
        if (dt > 0 && implied > CFG.maxPlausibleSpeed) {
            snap(st, p, ts, t);
            note(st, 'snap:teleport', Math.round(implied * 3.6) + 'km/h');
            return st;
        }
        if (sameInstant && dist > CFG.burstJumpMeters) {
            // Burst with positions from different places: jump to the newest one.
            snap(st, p, ts, t);
            note(st, 'snap:burst-jump', Math.round(dist));
            return st;
        }
        if (!dt && !st.speedEma && dist > CFG.snapMeters) {
            snap(st, p, ts, t);
            note(st, 'snap:unknown-jump', Math.round(dist));
            return st;
        }

        // ---- accept the fix --------------------------------------------------
        if (!burst) {
            if (wallGap > CFG.minSegMs && wallGap < CFG.offlineMs * 4) {
                st.intervalEma = ema(st.intervalEma, wallGap, CFG.intervalEmaAlpha);
            }
            var est = 0;
            if (dt > 0) est = implied;
            else if (wallGap > CFG.minSegMs) est = dist / (wallGap / 1000);
            if (est >= CFG.minVisualSpeed && est <= CFG.maxPlausibleSpeed) {
                st.speedEma = ema(st.speedEma, est, CFG.speedEmaAlpha);
            }
        }
        st.lastArrivalWall = t;
        st.lastFixWall = t;
        if (ts) st.lastFixTs = ts;
        else if (dt === 0 && !st.lastFixTs) st.lastFixTs = 0;

        var previousSeg = st.seg;
        if (previousSeg) {
            // Mid-glide retarget: steer towards the new target from the position
            // the user can actually see (st.cur is the last rendered frame), so a
            // late fix can never cause a visible jump. Only the direction, the
            // remaining distance and the pace change.
            previousSeg.__done = true;          // late road responses are ignored
            var segNew = startSegment(st, p, t, opts);
            var remain = distance(segNew.from, p);
            segNew.dur = suggestedDuration(st, remain, t);
            note(st, 'retarget', Math.round(remain));
        } else {
            startSegment(st, p, t, opts);
            note(st, burst ? 'burst:latest' : 'glide', Math.round(dist));
        }
        return st;
    }

    function segPosition(seg, k, t) {
        var pos;
        if (seg.path) {
            pos = alongPath(seg.path, easeK(k));
            if (seg.pathAt) {
                // Blend the road geometry in over CFG.road.blendMs so the marker
                // never jumps from the straight line onto the road.
                var m = clamp((t - seg.pathAt) / CFG.road.blendMs, 0, 1);
                if (m < 1) pos = mix(mix(seg.from, seg.to, easeK(k)), pos, m);
                else seg.pathAt = 0;
            }
        } else {
            pos = mix(seg.from, seg.to, easeK(k));
        }
        return pos;
    }

    function step(st, t) {
        var layer = st.layer;
        var map = layer._map;
        if (!map) {                       // marker detached (cluster/removed): park it
            if (st.target) st.cur = clone(st.target);
            st.seg = null;
            return false;
        }

        if (st.seg) {
            var seg = st.seg;
            var k = seg.dur > 0 ? (t - seg.t0) / seg.dur : 1;
            if (k >= 1) {
                st.cur = clone(seg.to);
                st.lastSegLen = seg.path ? seg.path.total : distance(seg.from, seg.to);
                st.lastBearing = distance(seg.from, seg.to) > 1 ? bearing(seg.from, seg.to) : st.lastBearing;
                st.segEndedAt = t;
                st.coastFrom = clone(st.cur);        // coasting is measured from here
                st.coastCap = Math.min(CFG.coastMaxMeters, st.lastSegLen * CFG.coastSegRatio);
                seg.__done = true;
                st.seg = null;
                layer.setLatLng({ lat: st.cur.lat, lng: st.cur.lng });
                st.frames++;
                return true;              // fall through to coasting/keep alive
            }
            if (k < 0) k = 0;
            st.cur = segPosition(seg, k, t);
            layer.setLatLng({ lat: st.cur.lat, lng: st.cur.lng });
            st.frames++;
            return true;
        }

        // ---- coasting: no fix arrived yet, keep creeping a little ------------
        if (!CFG.coast || !st.segEndedAt || !st.lastBearing || !st.cur) return false;
        var silent = t - st.segEndedAt;
        if (silent > CFG.coastMaxMs) return false;
        if (st.lastFixWall && (t - st.lastFixWall) > Math.max(CFG.offlineMs, st.intervalEma * CFG.offlineFactor)) return false;

        var cap = Math.min(CFG.coastMaxMeters, st.lastSegLen * CFG.coastSegRatio);
        if (cap < 3) return false;
        var creep = cap * (1 - Math.exp(-silent / CFG.coastTauMs));
        if (creep <= 0.05) return true;
        st.cur = project(st.coastFrom || st.cur, st.lastBearing, creep);
        layer.setLatLng({ lat: st.cur.lat, lng: st.cur.lng });
        st.frames++;
        return true;
    }

    // ------------------------------------------------------------- follow mode
    function followLayer() { return follow ? follow.layer : null; }

    function bindFollowEvents(map) {
        if (!map || map.__gpsSmoothBound) return;
        map.__gpsSmoothBound = true;
        map.on('dragstart', function () {
            if (!follow || !CFG.follow.pauseOnDrag) return;
            follow.userPaused = true;
            notifyFollow();
        });
    }

    function followStep(t) {
        if (!follow) return false;
        var layer = follow.layer;
        var map = follow.map || layer._map;
        if (!map || !layer._map || !map.hasLayer(layer)) { unfollow('layer-gone'); return false; }
        if (follow.userPaused || map._animatingZoom) return true;

        var pt = map.latLngToContainerPoint(layer.getLatLng());
        var size = map.getSize();
        if (!size || !size.x) return true;

        var dzx = size.x * CFG.follow.deadZoneX;
        var dzy = size.y * CFG.follow.deadZoneY;
        var dx = 0, dy = 0;

        if (pt.x < dzx) dx = pt.x - dzx;
        else if (pt.x > size.x - dzx) dx = pt.x - (size.x - dzx);
        if (pt.y < dzy) dy = pt.y - dzy;
        else if (pt.y > size.y - dzy) dy = pt.y - (size.y - dzy);

        if (!dx && !dy) return true;

        // Fractional correction every frame => exponential (eased) catch-up that
        // keeps pace with the marker instead of snapping or lagging behind.
        var err = Math.sqrt(dx * dx + dy * dy);
        var alpha = CFG.follow.alpha * (1 + Math.min(err / Math.max(size.x, size.y), 1));

        // Leaflet convention: map.panBy([px, py]) moves the *content* by that
        // offset, so a fixed marker moves by -offset on screen. dx/dy are how
        // far the marker sits outside the dead zone, so panning by +dx/+dy is
        // what pulls the marker back towards the middle of the viewport.
        //
        // Sub-pixel accumulator: Leaflet can only move integral pixels, so
        // rounding each step in isolation would stall the pan just outside the
        // dead zone. The remainder is carried to the next frame instead.
        follow.accX = (follow.accX || 0) + dx * alpha;
        follow.accY = (follow.accY || 0) + dy * alpha;
        var mag = Math.sqrt(follow.accX * follow.accX + follow.accY * follow.accY);
        if (mag > CFG.follow.maxPanPerFrame) {
            var scale = CFG.follow.maxPanPerFrame / mag;
            follow.accX *= scale;
            follow.accY *= scale;
        }
        var pxx = Math.round(follow.accX), pyy = Math.round(follow.accY);
        if (!pxx && !pyy) return true;
        follow.accX -= pxx;
        follow.accY -= pyy;
        map.panBy([pxx, pyy], { animate: false, noMoveStart: true, duration: 0 });
        follow.lastPanAt = t;
        return true;
    }

    function notifyFollow() {
        if (typeof followCb === 'function') {
            try { followCb(!!(follow && !follow.userPaused)); } catch (e) { /* noop */ }
        }
    }

    function followTarget(layer, options) {
        options = options || {};
        var map = options.map || (layer && layer._map);
        if (!layer || !map || typeof layer.getLatLng !== 'function') return false;
        if (!map.hasLayer(layer)) return false;
        var changed = !follow || follow.layer !== layer;
        follow = { layer: layer, map: map, userPaused: false, lastPanAt: 0 };
        bindFollowEvents(map);
        if (options.center !== false) {
            // Animated pan (never setView/fitBounds): Leaflet's own PosAnimation.
            try { map.panTo(layer.getLatLng(), { animate: true, duration: 0.6, easeLinearity: 0.25 }); }
            catch (e) { /* older Leaflet: the dead-zone step will pull it in anyway */ }
        }
        notifyFollow();
        wake();
        return true;
    }

    function unfollow(why) {
        if (!follow) { notifyFollow(); return false; }
        var wasPaused = follow.userPaused;
        follow = null;
        notifyFollow();
        if (CFG.debug && win.console) win.console.log('[GpsSmooth] unfollow', why || '');
        return true;
    }

    function resumeFollow() {
        if (!follow) return false;
        follow.userPaused = false;
        notifyFollow();
        wake();
        return true;
    }

    function isFollowing() { return !!(follow && !follow.userPaused); }

    // -------------------------------------------------------------- main loop
    function tick() {
        rafId = null;
        var t = now(), keep = [], i;

        if (isHidden()) {
            active = [];
            syncFollow();
            return;
        }

        for (i = 0; i < active.length; i++) {
            if (step(active[i], t)) keep.push(active[i]);
        }
        active = keep;

        followStep(t);

        if (active.length || follow) rafId = requestFrame(tick);
    }

    // ------------------------------------------------------- lifecycle helpers
    function forget(layer) {
        if (!layer) return;
        var st = layer.__gpsSmooth;
        if (st) {
            var i = active.indexOf(st);
            if (i >= 0) active.splice(i, 1);
        }
        if (follow && follow.layer === layer) unfollow('forget');
        delete layer.__gpsSmooth;
    }

    function place(layer, position) {
        var p = toLatLng(position);
        if (!p || !layer || typeof layer.setLatLng !== 'function') return;
        var st = layer.__gpsSmooth;
        if (st) snap(st, p, 0, now());
        else layer.setLatLng({ lat: p.lat, lng: p.lng });
    }

    function setEnabled(value) {
        CFG.enabled = !!value;
        if (!CFG.enabled) {
            var t = now();
            for (var i = 0; i < active.length; i++) {
                var st = active[i];
                if (st.target) snap(st, st.target, 0, t);
            }
            active = [];
        }
    }

    function syncFollow() {
        if (!follow) return;
        var map = follow.map || follow.layer._map;
        if (!map || !follow.layer._map) { unfollow('layer-gone'); return; }
        wake();
    }

    if (doc && doc.addEventListener) {
        doc.addEventListener('visibilitychange', function () {
            if (isHidden()) {
                active = [];
                return;
            }
            // Back in the foreground: snap every glide to the latest fix so the
            // marker is instantly correct, then resume smooth movement.
            var t = now();
            for (var i = 0; i < active.length; i++) {
                var st = active[i];
                if (st.target) snap(st, st.target, 0, t);
            }
            active = [];
            if (follow) follow.userPaused = false;
            notifyFollow();
            wake();
        });
    }

    // ------------------------------------------------------------- OSRM hook
    function decodePolyline(str, precision) {
        var index = 0, lat = 0, lng = 0, out = [], f = Math.pow(10, precision || 5);
        while (index < str.length) {
            for (var k = 0; k < 2; k++) {
                var b, shift = 0, result = 0;
                do { b = str.charCodeAt(index++) - 63; result |= (b & 0x1f) << shift; shift += 5; }
                while (b >= 0x20);
                var d = (result & 1) ? ~(result >> 1) : (result >> 1);
                if (k === 0) lat += d; else lng += d;
            }
            out.push({ lat: lat / f, lng: lng / f });
        }
        return out;
    }

    /**
     * Enable road-snapped gliding through an OSRM-compatible router.
     * Usage: GpsSmooth.enableOsrm('https://router.project-osrm.org');
     * Nothing is fetched unless a segment is longer than cfg.road.minMeters,
     * and any failure silently falls back to straight-line interpolation.
     */
    function enableOsrm(base) {
        base = (base || '').replace(/\/+$/, '');
        if (!base) return false;
        CFG.road.provider = function (from, to, done) {
            var url = base + '/route/v1/driving/' + from.lng + ',' + from.lat + ';' + to.lng + ',' + to.lat +
                '?overview=full&geometries=polyline&annotations=false';
            var finish = function (data) {
                try {
                    var r = data && data.routes && data.routes[0];
                    done(r && r.geometry ? decodePolyline(r.geometry) : null);
                } catch (e) { done(null); }
            };
            if (win.fetch) {
                win.fetch(url).then(function (r) { return r.json(); }).then(finish)['catch'](function () { done(null); });
            } else if (win.XMLHttpRequest) {
                var xhr = new win.XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.onload = function () {
                    try { finish(JSON.parse(xhr.responseText)); } catch (e) { done(null); }
                };
                xhr.onerror = function () { done(null); };
                xhr.send();
            } else {
                done(null);
            }
        };
        return true;
    }

    function disableRoad() { CFG.road.provider = null; roadCache = []; }

    // ------------------------------------------------------------------ exports
    win.GpsSmooth = {
        version: VERSION,
        cfg: CFG,
        move: move,
        place: place,
        forget: forget,
        setEnabled: setEnabled,
        follow: followTarget,
        unfollow: unfollow,
        resumeFollow: resumeFollow,
        isFollowing: isFollowing,
        followedLayer: followLayer,
        onFollowChange: function (fn) { followCb = fn; },
        enableOsrm: enableOsrm,
        disableRoad: disableRoad,
        decodePolyline: decodePolyline,
        diagnostics: function () { return diagnostics; },
        stats: function () {
            return {
                active: active.length,
                following: !!follow,
                paused: !!(follow && follow.userPaused),
                frames: active.reduce(function (a, s) { return a + s.frames; }, 0)
            };
        },
        // ---- test hooks (also handy from the console) ----
        _tick: function (t) { if (typeof t === 'number' && !CFG.now) CFG.now = function () { return t; }; tick(); },
        _state: function (layer) { return layer.__gpsSmooth || null; },
        _active: function () { return active; },
        _reset: function () {
            if (rafId !== null && CFG.scheduler && CFG.scheduler.cancel) CFG.scheduler.cancel(rafId);
            rafId = null;
            active = [];
            follow = null;
            roadCache = [];
            roadInFlight = {};
            diagnostics = {};
        }
    };
}(typeof window !== 'undefined' ? window : this, typeof document !== 'undefined' ? document : null));

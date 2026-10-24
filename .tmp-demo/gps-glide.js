/*!
 * gps-glide.js v3.0 - live GPS marker playback for Leaflet
 * ---------------------------------------------------------------------------
 * This replaces v2.2, whose forward-projection ("keep sliding after the last
 * fix") invented up to 120 s / 1500 m of movement from stale speed + course.
 * That is what made parked/slow vehicles drift off the road and then jump
 * back when the next real fix arrived.
 *
 * Rules now:
 *   - only real, valid fixes are animated; there is no dead reckoning and no
 *     movement past the newest received fix;
 *   - fixes are ordered by device fix time (with packet id as tie-breaker),
 *     not by packet-arrival order, and older fixes can never move the marker
 *     backwards;
 *   - the marker heading is calculated from the actual path it travels, with
 *     the GPS course used only as a fallback; the last heading is kept while
 *     the vehicle is stationary;
 *   - a jump that cannot be driven at a plausible speed is snapped, never
 *     flown/interpolated through buildings;
 *   - optional OSRM map matching replaces the straight GPS polyline only when
 *     every point matches within its accuracy radius and the matched route is
 *     plausible; otherwise the real GPS polyline is used unchanged;
 *   - one requestAnimationFrame loop, one active path per marker.
 *
 * No dependencies. Disable with localStorage gpsGlide=0. Disable road matching
 * with localStorage gpsRoad=0 (the engine then follows the raw GPS polyline).
 */
(function (global) {
    'use strict';

    var cfg = {
        minMoveM: 6,              // smaller changes are GPS jitter / duplicate
        snapM: 1500,              // no-timestamp jump that must not be animated
        maxPlausibleSpeedMs: 70,  // 252 km/h - above this a jump is a teleport
        maxVisualSpeedMs: 55,     // 198 km/h pace ceiling
        minSegMs: 800,
        maxGlideMs: 20000,        // never stretch a short batch over minutes
        maxCatchupMs: 12000,      // normal live display lag
        stationarySpeedKmh: 1.5,
        stationaryMoveM: 15,
        maxTurnDegPerSec: 160,
        minSegmentBearingM: 3,
        debug: false,

        // Optional road geometry. The public OSRM instance is used by default,
        // selected device only, with strict matching and a short wait. If it is
        // unreachable the engine simply uses the real GPS polyline.
        road: {
            enabled: true,
            base: 'https://router.project-osrm.org',
            onlySelected: true,
            minMeters: 35,
            waitMs: 450,
            timeoutMs: 1100,
            maxSnapM: 35,
            minSnapM: 15,
            snapAccuracyFactor: 1.25,
            minConfidence: 0.5,
            minRatio: 0.7,
            maxRatio: 1.6,
            requestGapMs: 4000,
            failCooldownMs: 30000,
            cacheSize: 24
        },

        // Test hooks
        now: null,
        scheduler: null
    };

    try {
        if (global.localStorage.getItem('gpsRoad') === '0') cfg.road.enabled = false;
        if (global.localStorage.getItem('gpsRoadUrl')) cfg.road.base = global.localStorage.getItem('gpsRoadUrl');
    } catch (e) { /* private mode */ }

    var states = new WeakMap();
    var active = [];
    var raf = null;
    var log = [];
    var roadCache = [];

    var EARTH_R = 6371008.8;
    var DEG = Math.PI / 180;

    function isOn() {
        try { return global.localStorage.getItem('gpsGlide') !== '0'; } catch (e) { return true; }
    }

    function nowMs() {
        if (cfg.now) return cfg.now();
        return (global.performance && global.performance.now) ? global.performance.now() : Date.now();
    }

    function requestFrame(fn) {
        if (cfg.scheduler && cfg.scheduler.request) return cfg.scheduler.request(fn);
        if (global.requestAnimationFrame) return global.requestAnimationFrame(fn);
        return global.setTimeout(function () { fn(nowMs()); }, 16);
    }

    function isHidden() {
        return !!(global.document && global.document.hidden);
    }

    function clamp(v, lo, hi) { return v < lo ? lo : (v > hi ? hi : v); }

    function distance(a, b) {
        if (!a || !b) return 0;
        var dLat = (b.lat - a.lat) * DEG;
        var dLng = (b.lng - a.lng) * DEG;
        var h = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(a.lat * DEG) * Math.cos(b.lat * DEG) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return 2 * EARTH_R * Math.asin(Math.min(1, Math.sqrt(h)));
    }

    function bearing(a, b) {
        var la1 = a.lat * DEG;
        var la2 = b.lat * DEG;
        var dLng = (b.lng - a.lng) * DEG;
        var y = Math.sin(dLng) * Math.cos(la2);
        var x = Math.cos(la1) * Math.sin(la2) - Math.sin(la1) * Math.cos(la2) * Math.cos(dLng);
        return (Math.atan2(y, x) / DEG + 360) % 360;
    }

    function normAngle(a) {
        a = a % 360;
        return a < 0 ? a + 360 : a;
    }

    function parsePos(p) {
        if (!p) return null;

        var q = null;

        if (typeof p.lat === 'number' && typeof p.lng === 'number') {
            q = { lat: p.lat, lng: p.lng };
        } else if (typeof p.lat === 'function' && typeof p.lng === 'function') {
            q = { lat: p.lat(), lng: p.lng() };
        } else if (p.length === 2) {
            q = { lat: parseFloat(p[0]), lng: parseFloat(p[1]) };
        } else if (global.L && typeof global.L.latLng === 'function') {
            try {
                var ll = global.L.latLng(p);
                if (ll) q = { lat: ll.lat, lng: ll.lng };
            } catch (e) { /* ignore */ }
        }

        if (!q || !isFinite(q.lat) || !isFinite(q.lng)) return null;
        if (Math.abs(q.lat) > 90 || Math.abs(q.lng) > 180) return null;
        if (Math.abs(q.lat) < 1e-9 && Math.abs(q.lng) < 1e-9) return null;

        return { lat: q.lat, lng: q.lng };
    }

    function tsMs(ts) {
        var v = typeof ts === 'string' ? parseFloat(ts) : ts;
        if (!isFinite(v) || v <= 0) return 0;
        if (v < 1e11) v *= 1000;
        if (v < 1e11) return 0;
        if (v > Date.now() + 86400000) return 0;
        return Math.round(v);
    }

    function courseOf(v) {
        if (v === null || typeof v === 'undefined' || v === '') return null;

        var c = typeof v === 'string' ? parseFloat(v) : v;
        // -1 / >360 is the usual "course unavailable" marker; treat it as
        // unknown instead of turning the car to 359 degrees.
        if (!isFinite(c) || c < 0 || c > 360) return null;
        return normAngle(c);
    }

    function param(row, camel, snake) {
        if (row[camel] !== undefined && row[camel] !== null) return row[camel];
        return row[snake];
    }

    function normaliseTrack(track) {
        var out = [], i, p, lat, lng, t, id, valid, sp, co, acc;

        if (!track || !track.length) return out;

        for (i = 0; i < track.length; i++) {
            p = track[i] || {};
            lat = parseFloat(param(p, 'lat', 'latitude'));
            lng = parseFloat(param(p, 'lng', 'longitude'));

            if (!isFinite(lat) || !isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180)
                continue;
            if (Math.abs(lat) < 1e-9 && Math.abs(lng) < 1e-9)
                continue;

            valid = param(p, 'v', 'valid');
            if (valid === 0 || valid === '0' || valid === false)
                continue;

            t = tsMs(param(p, 't', 'fixtime_ms'));
            id = parseFloat(param(p, 'id', 'position_id'));
            sp = parseFloat(param(p, 'sp', 'speed'));
            co = courseOf(param(p, 'co', 'course'));
            acc = parseFloat(param(p, 'a', 'accuracy'));

            out.push({
                id: isFinite(id) ? id : 0,
                t: t,
                lat: lat,
                lng: lng,
                sp: isFinite(sp) ? sp : null,
                co: co,
                a: isFinite(acc) ? acc : null
            });
        }

        out.sort(function (a, b) {
            if (a.t && b.t && a.t !== b.t) return a.t - b.t;
            return (a.id || 0) - (b.id || 0);
        });

        // A burst sharing one fixtime has no real time to animate across.
        // Keep only its newest row, so it can never pull the marker backwards.
        var dedup = [], lastT = -1;
        for (i = 0; i < out.length; i++) {
            p = out[i];
            if (p.t && lastT > 0 && p.t === lastT) {
                dedup[dedup.length - 1] = p;
                continue;
            }
            dedup.push(p);
            if (p.t) lastT = p.t;
        }

        return dedup;
    }

    function compareFix(a, b) {
        if (a.t && b.t && a.t !== b.t) return a.t - b.t;
        return (a.id || 0) - (b.id || 0);
    }

    function sameCoords(a, b) {
        return Math.abs(a.lat - b.lat) < 1e-9 && Math.abs(a.lng - b.lng) < 1e-9;
    }

    function pointOf(fix) {
        return { lat: fix.lat, lng: fix.lng };
    }

    function buildPath(points) {
        var pts = [], acc = [0], total = 0, i, p, prev;

        for (i = 0; i < points.length; i++) {
            p = parsePos(points[i]);
            if (!p) continue;
            prev = pts.length ? pts[pts.length - 1] : null;
            if (prev && distance(prev, p) < 0.35) continue;
            pts.push(p);
        }

        if (pts.length < 2) return null;

        for (i = 1; i < pts.length; i++) {
            total += distance(pts[i - 1], pts[i]);
            acc.push(total);
        }

        return total > 0 ? { pts: pts, acc: acc, total: total } : null;
    }

    function pointAt(path, dist) {
        var pts = path.pts, acc = path.acc, i, len, f;

        if (!pts || pts.length < 2) return pts ? { lat: pts[0].lat, lng: pts[0].lng } : null;
        if (dist <= 0) return { lat: pts[0].lat, lng: pts[0].lng };
        if (dist >= path.total) {
            return { lat: pts[pts.length - 1].lat, lng: pts[pts.length - 1].lng };
        }

        for (i = 1; i < pts.length; i++) {
            if (acc[i] >= dist) {
                len = acc[i] - acc[i - 1];
                f = len > 0 ? (dist - acc[i - 1]) / len : 0;
                return {
                    lat: pts[i - 1].lat + (pts[i].lat - pts[i - 1].lat) * f,
                    lng: pts[i - 1].lng + (pts[i].lng - pts[i - 1].lng) * f
                };
            }
        }

        return { lat: pts[pts.length - 1].lat, lng: pts[pts.length - 1].lng };
    }

    function pathBearing(path, dist) {
        if (!path || path.total < 0.5) return null;

        var a = pointAt(path, Math.max(0, dist - 1));
        var b = pointAt(path, Math.min(path.total, dist + 5));

        if (distance(a, b) >= cfg.minSegmentBearingM) return bearing(a, b);

        var pts = path.pts, i;
        for (i = pts.length - 1; i > 0; i--) {
            if (distance(pts[i - 1], pts[i]) >= cfg.minSegmentBearingM) {
                return bearing(pts[i - 1], pts[i]);
            }
        }

        return null;
    }

    function lastPathBearing(path) {
        if (!path) return null;
        var pts = path.pts;
        for (var i = pts.length - 1; i > 0; i--) {
            if (distance(pts[i - 1], pts[i]) >= cfg.minSegmentBearingM) {
                return bearing(pts[i - 1], pts[i]);
            }
        }
        return null;
    }

    function pathLength(points) {
        var total = 0, i;
        for (i = 1; i < points.length; i++) total += distance(points[i - 1], points[i]);
        return total;
    }

    // ------------------------------------------------------------------ state
    function stateOf(layer) {
        var st = states.get(layer);
        if (st) return st;

        st = {
            layer: layer,
            cur: null,
            lastPos: null,
            lastFixT: 0,
            lastFixId: 0,
            lastArrivalWall: 0,
            lastTick: 0,
            heading: 0,
            hasHeading: false,
            rotate: true,
            playing: false,
            pts: null,
            cum: null,
            len: 0,
            t0: 0,
            dur: 1,
            pathKind: 'gps',
            pending: null,
            roadSeq: 0,
            roadTimer: null,
            roadReqAt: 0,
            roadFailAt: 0,
            roadKey: '',
            frames: 0,
            snaps: 0
        };

        states.set(layer, st);
        return st;
    }

    function trace(layer, action, extra) {
        var row = {
            at: Date.now(),
            dev: layer && layer.options ? layer.options.device_id : null,
            action: action,
            extra: extra || null
        };

        log.push(row);
        if (log.length > 150) log.shift();

        if (cfg.debug && global.console) {
            global.console.log('[GpsGlide]', action, row.dev, extra || '');
        }
    }

    function setHeading(st, value, immediate) {
        if (!isFinite(value)) return;
        value = normAngle(value);

        if (!st.hasHeading || immediate) {
            st.heading = value;
            st.hasHeading = true;
            return;
        }

        var delta = ((value - st.heading + 540) % 360) - 180;
        var t = nowMs();
        var dt = st.lastTick ? clamp((t - st.lastTick) / 1000, 0.001, 0.12) : 0.016;
        var maxTurn = cfg.maxTurnDegPerSec * dt;

        if (Math.abs(delta) <= maxTurn) {
            st.heading = normAngle(value);
        } else {
            st.heading = normAngle(st.heading + (delta > 0 ? maxTurn : -maxTurn));
        }
    }

    function applyAngle(st, refresh) {
        if (!st || !st.rotate || !st.hasHeading || !st.layer) return;

        st.layer.options.iconAngle = st.heading;

        if (refresh && typeof st.layer.setIconAngle === 'function' && st.layer._map) {
            try { st.layer.setIconAngle(st.heading); } catch (e) { /* ignore */ }
        }
    }

    function placeOnMap(st, p, refresh) {
        if (!st || !st.layer || typeof st.layer.setLatLng !== 'function') return;
        applyAngle(st, false);
        try {
            st.layer.setLatLng({ lat: p.lat, lng: p.lng });
            if (refresh) applyAngle(st, true);
        } catch (e) { /* layer removed */ }
    }

    function stopAnimation(st) {
        st.playing = false;
        st.pts = null;
        st.cum = null;
        st.len = 0;
        var i = active.indexOf(st);
        if (i >= 0) active.splice(i, 1);
    }

    function clearPending(st) {
        st.roadSeq++;
        st.pending = null;
        if (st.roadTimer) {
            clearTimeout(st.roadTimer);
            st.roadTimer = null;
        }
    }

    function snapTo(st, fix, heading, reason) {
        clearPending(st);

        var p = pointOf(fix);

        st.cur = p;
        st.lastPos = p;

        if (fix.t) st.lastFixT = fix.t;
        if (fix.id) st.lastFixId = fix.id;

        if (isFinite(heading)) {
            setHeading(st, heading, true);
        }

        stopAnimation(st);
        st.snaps++;
        placeOnMap(st, p, true);

        trace(st.layer, reason || 'snap', { t: fix.t || 0, id: fix.id || 0 });
    }

    function initialHeading(fix, track, hint) {
        var i;

        if (track && track.length > 1) {
            for (i = track.length - 1; i > 0; i--) {
                if (distance(track[i - 1], track[i]) >= 5) {
                    return bearing(track[i - 1], track[i]);
                }
            }
        }

        if (fix && fix.co !== null && fix.co !== undefined) return fix.co;

        var c = courseOf(hint && hint.course);
        if (c !== null) return c;

        return 0;
    }

    function targetFix(pos, hint, track) {
        var p = parsePos(pos);
        if (!p) return null;

        var t = tsMs(hint && hint.ts);
        var id = parseFloat(hint && hint.positionId);
        var sp = parseFloat(hint && hint.speed);
        var co = courseOf(hint && hint.course);

        var fix = {
            lat: p.lat,
            lng: p.lng,
            t: t,
            id: isFinite(id) ? id : 0,
            sp: isFinite(sp) ? sp : null,
            co: co,
            a: null
        };

        // The live feed normally has the same newest row in track and in the
        // device fields. If track is newer (a delayed main payload), use track.
        if (track && track.length) {
            var last = track[track.length - 1];
            if (!fix.t && last.t) return last;
            if (fix.t && last.t && last.t > fix.t) return last;
            if (fix.t && last.t && last.t === fix.t && last.id > fix.id) return last;
        }

        return fix;
    }

    function isNewer(fix, st) {
        if (!fix) return false;

        if (fix.t && st.lastFixT) {
            if (fix.t > st.lastFixT) return true;
            if (fix.t === st.lastFixT) return fix.id > st.lastFixId;
            return false;
        }

        if (fix.id && st.lastFixId) return fix.id > st.lastFixId;

        // No time/id metadata (legacy payload): fall back to arrival order.
        // The caller still filters sub-metre jitter before animating.
        if (!fix.t && !fix.id) {
            return !st.lastPos || distance(pointOf(fix), st.lastPos) > 0.01;
        }

        return !st.lastFixT;
    }

    function collectNewFixes(st, track, target) {
        var raw = [], i, p;

        for (i = 0; i < track.length; i++) {
            p = track[i];
            if (!isNewer(p, st)) continue;
            raw.push(p);
        }

        if (target && isNewer(target, st)) {
            var duplicate = false;
            for (i = 0; i < raw.length; i++) {
                if (raw[i].t === target.t && raw[i].id === target.id) {
                    duplicate = true;
                    break;
                }
            }
            if (!duplicate) raw.push(target);
        }

        raw.sort(compareFix);

        var out = [], lastT = -1;
        for (i = 0; i < raw.length; i++) {
            p = raw[i];
            if (p.t && lastT > 0 && p.t === lastT) {
                out[out.length - 1] = p;
                continue;
            }
            out.push(p);
            if (p.t) lastT = p.t;
        }

        return out;
    }

    function plausibleFixSequence(fixes, fromFix) {
        var prev = fromFix ? pointOf(fromFix) : null;
        var prevT = fromFix && fromFix.t ? fromFix.t : 0;
        var i, dt;

        for (i = 0; i < fixes.length; i++) {
            var p = fixes[i];
            var d = prev ? distance(prev, p) : 0;

            if (prevT && p.t) {
                dt = (p.t - prevT) / 1000;
                if (dt > 0 && d / dt > cfg.maxPlausibleSpeedMs) return false;
                if (dt <= 0 && d > cfg.snapM) return false;
                if (dt > 0 && dt < 2 && d > cfg.snapM) return false;
            } else if (d > cfg.snapM) {
                return false;
            }

            prev = pointOf(p);
            prevT = p.t || prevT;
        }

        return true;
    }

    // --------------------------------------------------------------- road data
    function decodePolyline(str, precision) {
        var index = 0, lat = 0, lng = 0, out = [];
        var f = Math.pow(10, precision || 5);

        while (index < str.length) {
            var k, b, shift, result, d;

            for (k = 0; k < 2; k++) {
                shift = 0;
                result = 0;
                do {
                    b = str.charCodeAt(index++) - 63;
                    result |= (b & 0x1f) << shift;
                    shift += 5;
                } while (b >= 0x20);

                d = (result & 1) ? ~(result >> 1) : (result >> 1);

                if (k === 0) lat += d;
                else lng += d;
            }

            out.push({ lat: lat / f, lng: lng / f });
        }

        return out;
    }

    function selectedDevice(layer) {
        try {
            if (!global.app || !global.app.devices || typeof global.app.devices.getSelected !== 'function')
                return false;

            var selected = global.app.devices.getSelected();

            if (selected === null || typeof selected === 'undefined')
                return false;

            var id = layer && layer.options ? layer.options.device_id : null;
            return String(selected) === String(id);
        } catch (e) {
            return false;
        }
    }

    function shouldMatch(layer, path) {
        if (!cfg.road.enabled || !global.fetch) return false;
        if (!path || path.total < cfg.road.minMeters) return false;
        if (cfg.road.onlySelected && !selectedDevice(layer)) return false;
        return true;
    }

    function roadCacheGet(key) {
        for (var i = roadCache.length - 1; i >= 0; i--) {
            if (roadCache[i].key === key) return roadCache[i].points;
        }
        return null;
    }

    function roadCacheSet(key, points) {
        roadCache.push({ key: key, points: points });
        while (roadCache.length > cfg.road.cacheSize) roadCache.shift();
    }

    function roadKey(points) {
        var parts = [], i;
        for (i = 0; i < points.length; i++) {
            parts.push(points[i].lat.toFixed(5) + ',' + points[i].lng.toFixed(5));
        }
        return parts.join('|');
    }

    // Snap only inside the fix's own accuracy radius. A 20 m error may be
    // snapped; a point that is 25 m away with 5 m accuracy is not trusted.
    function snapRadius(point) {
        var a = point && isFinite(point.a) ? point.a : null;
        if (a === null) return 25;
        return Math.max(cfg.road.minSnapM, Math.min(cfg.road.maxSnapM, a * cfg.road.snapAccuracyFactor));
    }

    function validateMatch(data, points) {
        if (!data || data.code !== 'Ok' || !data.matchings || data.matchings.length !== 1)
            return null;

        var matching = data.matchings[0];
        var tracepoints = data.tracepoints;

        if (!matching.geometry || !tracepoints || tracepoints.length !== points.length)
            return null;

        if (typeof matching.confidence === 'number' && matching.confidence < cfg.road.minConfidence)
            return null;

        var i, tp, snapped;
        for (i = 0; i < points.length; i++) {
            tp = tracepoints[i];
            if (!tp || !tp.location || tp.location.length < 2) return null;

            snapped = { lat: tp.location[1], lng: tp.location[0] };
            if (distance(points[i], snapped) > snapRadius(points[i])) return null;
        }

        var geometry = decodePolyline(matching.geometry);
        if (geometry.length < 2) return null;

        var rawLen = pathLength(points);
        var matchedLen = pathLength(geometry);

        if (rawLen > 40 && (matchedLen / rawLen < cfg.road.minRatio || matchedLen / rawLen > cfg.road.maxRatio))
            return null;

        if (distance(geometry[0], points[0]) > snapRadius(points[0])) return null;
        if (distance(geometry[geometry.length - 1], points[points.length - 1]) > snapRadius(points[points.length - 1])) return null;

        return geometry;
    }

    function requestRoad(st, points, seq, done) {
        var key = roadKey(points);
        var cached = roadCacheGet(key);

        if (cached) {
            done(cached);
            return;
        }

        var wall = Date.now();

        if (wall - st.roadReqAt < cfg.road.requestGapMs || wall - st.roadFailAt < cfg.road.failCooldownMs) {
            done(null);
            return;
        }

        st.roadReqAt = wall;
        st.roadKey = key;

        var coords = [], radiuses = [], i;

        for (i = 0; i < points.length; i++) {
            coords.push(points[i].lng.toFixed(6) + ',' + points[i].lat.toFixed(6));
            radiuses.push(Math.round(snapRadius(points[i])));
        }

        var url = cfg.road.base.replace(/\/+$/, '') +
            '/match/v1/driving/' + coords.join(';') +
            '?overview=full&geometries=polyline&tidy=false&gaps=split' +
            '&radiuses=' + radiuses.join(';') +
            '&annotations=false';

        var finished = false;
        var finish = function (result) {
            if (finished) return;
            finished = true;

            if (result) {
                roadCacheSet(key, result);
            } else {
                st.roadFailAt = Date.now();
            }

            done(result);
        };

        var controller = null;
        var timeout = null;

        try {
            if (global.AbortController) {
                controller = new global.AbortController();
                timeout = setTimeout(function () {
                    try { controller.abort(); } catch (e) { /* ignore */ }
                }, cfg.road.timeoutMs);
            }

            global.fetch(url, controller ? { signal: controller.signal } : {})
                .then(function (response) { return response.ok ? response.json() : null; })
                .then(function (data) {
                    if (timeout) clearTimeout(timeout);
                    finish(validateMatch(data, points));
                })
                ['catch'](function () {
                    if (timeout) clearTimeout(timeout);
                    finish(null);
                });

        } catch (e) {
            if (timeout) clearTimeout(timeout);
            finish(null);
        }
    }

    // The part of the current animation that has not been drawn yet. When a
    // newer fix arrives mid-animation, retargeting from the rendered position
    // straight to the new fix would cut the corner and skip the real fixes in
    // between. Keep the remaining path geometry, then append the new fixes.
    function remainingPoints(st, now) {
        if (!st.playing || !st.pts || !st.cum || !st.len) return st.cur ? [st.cur] : [];

        var path = { pts: st.pts, acc: st.cum, total: st.len };
        var k = clamp((now - st.t0) / st.dur, 0, 1);
        var dist = k * st.len;
        var out = [pointAt(path, dist)];
        var i;

        for (i = 1; i < st.pts.length; i++) {
            if (st.cum[i] > dist + 0.35) out.push(st.pts[i]);
        }

        return out;
    }

    function roadPoints(st, newFixes, now) {
        var pts = remainingPoints(st, now), i;

        for (i = 0; i < newFixes.length; i++) {
            pts.push(pointOf(newFixes[i]));
        }

        var clean = [], prev = null;
        for (i = 0; i < pts.length; i++) {
            if (!prev || distance(prev, pts[i]) > 0.35) clean.push(pts[i]);
            prev = pts[i];
        }

        return clean;
    }

    // ------------------------------------------------------------------ engine
    function beginMovement(st, points, dur, target, kind) {
        clearPending(st);

        var path = buildPath(points);
        if (!path) {
            stopAnimation(st);
            st.cur = pointOf(target);
            st.lastPos = st.cur;
            placeOnMap(st, st.cur, true);
            return;
        }

        st.pts = path.pts;
        st.cum = path.acc;
        st.len = path.total;
        st.t0 = nowMs();
        st.dur = Math.max(1, dur);
        st.pathKind = kind || 'gps';
        st.playing = true;
        st.lastTick = st.t0;
        st.cur = { lat: path.pts[0].lat, lng: path.pts[0].lng };

        // Do not start with the old bearing: a new path's first segment is the
        // only reliable heading when the vehicle is already moving.
        var firstBearing = pathBearing(path, 0);
        if (firstBearing !== null) {
            setHeading(st, firstBearing, !st.hasHeading);
        }

        if (active.indexOf(st) < 0) active.push(st);
        wake();

        trace(st.layer, 'play:' + st.pathKind, {
            m: Math.round(st.len),
            dur: Math.round(st.dur),
            fixes: points.length
        });
    }

    function finishPath(st, updateHeading) {
        var end = pointAt({ pts: st.pts, acc: st.cum, total: st.len }, st.len);
        var b = updateHeading ? lastPathBearing({ pts: st.pts, total: st.len }) : null;

        st.cur = end;
        st.lastPos = { lat: end.lat, lng: end.lng };
        stopAnimation(st);

        if (b !== null) setHeading(st, b, true);

        placeOnMap(st, end, true);
        trace(st.layer, 'arrived', { h: Math.round(st.heading) });
    }

    function step(st, now) {
        if (!st.playing || !st.layer) return false;

        if (!st.layer._map) {
            stopAnimation(st);
            return false;
        }

        var k = (now - st.t0) / st.dur;

        if (k >= 1) {
            finishPath(st, true);
            return false;
        }

        if (k < 0) k = 0;

        var pathRef = { pts: st.pts, acc: st.cum, total: st.len };
        var pos = pointAt(pathRef, k * st.len);
        var b = pathBearing(pathRef, k * st.len);

        if (b !== null) setHeading(st, b, false);

        st.cur = pos;
        st.lastTick = now;
        st.frames++;
        placeOnMap(st, pos, false);

        return true;
    }

    function tick(now) {
        raf = null;
        now = typeof now === 'number' ? now : nowMs();

        if (isHidden()) {
            for (var j = 0; j < active.length; j++) {
                var s = active[j];
                if (s && s.lastPos) {
                    s.cur = { lat: s.lastPos.lat, lng: s.lastPos.lng };
                    s.playing = false;
                    placeOnMap(s, s.cur, true);
                }
            }
            active = [];
            return;
        }

        var keep = [], i;

        for (i = 0; i < active.length; i++) {
            if (step(active[i], now)) keep.push(active[i]);
        }

        active = keep;

        if (active.length) wake();
    }

    function wake() {
        if (raf === null) raf = requestFrame(tick);
    }

    function updateStateTimes(st, fix) {
        if (fix.t) st.lastFixT = Math.max(st.lastFixT, fix.t);
        if (fix.id) st.lastFixId = Math.max(st.lastFixId, fix.id);
        st.lastArrivalWall = nowMs();
    }

    function move(layer, position, hint) {
        if (!layer || typeof layer.setLatLng !== 'function') return;

        var pos = parsePos(position);
        if (!pos) return;

        hint = hint || {};

        var now = nowMs();
        var track = normaliseTrack(hint.track);
        var target = targetFix(pos, hint, track);
        var st = states.get(layer);

        if (!target) return;

        // First sight of this marker: place it, learn its time/id and heading.
        if (!st) {
            st = stateOf(layer);
            st.rotate = hint.rotate !== false;

            st.cur = { lat: target.lat, lng: target.lng };
            st.lastPos = { lat: target.lat, lng: target.lng };
            st.lastFixT = target.t || 0;
            st.lastFixId = target.id || 0;
            st.lastArrivalWall = now;

            var heading = initialHeading(target, track, hint);
            st.heading = heading;
            st.hasHeading = true;
            st.lastTick = now;

            applyAngle(st, false);
            placeOnMap(st, st.cur, true);

            trace(layer, 'init', {
                t: target.t || 0,
                id: target.id || 0,
                h: Math.round(heading)
            });
            return;
        }

        if (hint.rotate === false) st.rotate = false;

        var newer = isNewer(target, st);

        if (!newer) {
            // A late/out-of-order packet must never pull the live marker back.
            // It remains available to history/reports because the server keeps
            // the row; only this live animation ignores it.
            applyAngle(st, true);
            trace(layer, 'stale', { t: target.t || 0, lastT: st.lastFixT || 0 });
            return;
        }

        var fixes = collectNewFixes(st, track, target);

        if (!fixes.length) {
            applyAngle(st, true);
            return;
        }

        var newest = fixes[fixes.length - 1];
        var prevFix = { lat: st.lastPos.lat, lng: st.lastPos.lng, t: st.lastFixT, id: st.lastFixId };
        var prevT = st.lastFixT;
        var prevArrival = st.lastArrivalWall;

        // Update the chronological watermark before any async road request, so
        // the same fixes cannot be replayed twice.
        updateStateTimes(st, newest);

        var jump = distance(st.lastPos, newest);

        if (!plausibleFixSequence(fixes, prevFix)) {
            var jumpHeading = lastPathBearing(st.pts ? { pts: st.pts, total: st.len } : null);
            if (jumpHeading === null) jumpHeading = st.hasHeading ? st.heading : initialHeading(newest, track, hint);
            snapTo(st, newest, jumpHeading, 'snap:jump');
            return;
        }

        var spanMs = 0;
        if (newest.t && prevT) spanMs = newest.t - prevT;

        if (!spanMs || spanMs < 0) {
            spanMs = now - (prevArrival || now);
        }

        // A long gap with no intermediate track is an offline/reconnect event.
        // Snapping is honest; gliding across blocks is not.
        if (spanMs > 180000) {
            snapTo(st, newest, st.hasHeading ? st.heading : initialHeading(newest, track, hint), 'snap:offline');
            return;
        }

        var reportedSpeed = parseFloat(hint.speed);
        if (!isFinite(reportedSpeed)) {
            reportedSpeed = newest.sp;
        }

        var accuracy = parseFloat(hint.accuracy);
        var stationary = isFinite(reportedSpeed) && reportedSpeed <= cfg.stationarySpeedKmh && jump < cfg.stationaryMoveM;
        var lowAccuracy = isFinite(accuracy) && accuracy > 100;

        if (lowAccuracy) {
            // No trustworthy fix: keep the last confirmed position/heading
            // instead of drawing the vehicle somewhere it may not be.
            applyAngle(st, true);
            trace(layer, 'ignore:low-accuracy', { a: Math.round(accuracy), m: Math.round(jump) });
            return;
        }

        if (jump < cfg.minMoveM || stationary) {
            // Stationary or sub-metre GPS noise. Keep both the marker position
            // and its heading; only the chronological watermark moved forward,
            // so a slow real movement can accumulate until it is visible.
            if (newest.co !== null && newest.co !== undefined && !st.hasHeading) {
                setHeading(st, newest.co, true);
            }
            applyAngle(st, true);
            trace(layer, 'still', { m: Math.round(jump) });
            return;
        }

        if (isHidden()) {
            snapTo(st, newest, st.heading, 'snap:hidden');
            return;
        }

        var pathPoints = roadPoints(st, fixes, now);
        var path = buildPath(pathPoints);

        if (!path) {
            st.lastPos = { lat: newest.lat, lng: newest.lng };
            applyAngle(st, true);
            return;
        }

        var dur = clamp(spanMs || cfg.maxCatchupMs, cfg.minSegMs, cfg.maxGlideMs);

        // Retargeting during an animation must use the time left in that
        // animation, not the whole interval since the previous fix again,
        // otherwise every poll stretches the marker further behind live.
        if (st.playing) {
            dur = Math.max(cfg.minSegMs, st.dur - (now - st.t0));
        }

        var needed = path.total / cfg.maxVisualSpeedMs * 1000;

        if (needed > dur) dur = needed;

        if (dur > cfg.maxGlideMs * 1.5) {
            snapTo(st, newest, st.hasHeading ? st.heading : initialHeading(newest, track, hint), 'snap:too-far');
            return;
        }

        // If a road provider is on, wait briefly for a trustworthy match. The
        // marker stays at the previous real fix during this short wait, so it
        // cannot jump while the request is in flight.
        if (shouldMatch(layer, path)) {
            var seq = ++st.roadSeq;
            var started = false;
            var start = function (matched) {
                if (started || st.roadSeq !== seq) return;
                started = true;
                if (st.roadTimer) {
                    clearTimeout(st.roadTimer);
                    st.roadTimer = null;
                }
                st.pending = null;

                var points = matched && matched.length > 1
                    ? [st.cur].concat(matched)
                    : pathPoints;

                var clean = [], prev = null, i;
                for (i = 0; i < points.length; i++) {
                    if (!prev || distance(prev, points[i]) > 0.35) clean.push(points[i]);
                    prev = points[i];
                }

                var roadPath = buildPath(clean);
                var roadDur = dur;

                if (roadPath) {
                    roadDur = Math.max(roadDur, roadPath.total / cfg.maxVisualSpeedMs * 1000);
                }

                beginMovement(st, clean, roadDur, newest, matched ? 'road' : 'gps');
            };

            st.pending = { seq: seq };

            st.roadTimer = setTimeout(function () {
                if (st.roadSeq === seq) start(null);
            }, cfg.road.waitMs);

            requestRoad(st, pathPoints, seq, start);
            st.lastPos = { lat: newest.lat, lng: newest.lng };
            return;
        }

        beginMovement(st, pathPoints, dur, newest, 'gps');
        st.lastPos = { lat: newest.lat, lng: newest.lng };
    }

    function place(layer, position, hint) {
        if (!layer) return;

        var pos = parsePos(position);
        if (!pos) return;

        hint = hint || {};

        var st = states.get(layer);
        var track = normaliseTrack(hint.track);
        var target = targetFix(pos, hint, track) || { lat: pos.lat, lng: pos.lng, t: 0, id: 0, sp: null, co: null, a: null };

        if (!st) {
            st = stateOf(layer);
            st.rotate = hint.rotate !== false;
            st.cur = { lat: target.lat, lng: target.lng };
            st.lastPos = { lat: target.lat, lng: target.lng };
            st.lastFixT = target.t || 0;
            st.lastFixId = target.id || 0;
            st.heading = initialHeading(target, track, hint);
            st.hasHeading = true;
            placeOnMap(st, st.cur, true);
            return;
        }

        if (target.t && st.lastFixT && target.t < st.lastFixT) {
            applyAngle(st, true);
            return;
        }

        var heading = st.hasHeading ? st.heading : initialHeading(target, track, hint);
        var course = courseOf(hint.course);
        if (!st.hasHeading && course !== null) heading = course;

        updateStateTimes(st, target);
        snapTo(st, target, heading, 'place');
    }

    function forget(layer) {
        if (!layer) return;
        var st = states.get(layer);
        if (!st) return;

        clearPending(st);
        stopAnimation(st);
        states['delete'](layer);
    }

    function setEnabled(value) {
        if (!value) {
            var list = active.slice();
            for (var i = 0; i < list.length; i++) {
                var st = list[i];
                if (st && st.lastPos) snapTo(st, st.lastPos, st.heading, 'snap:disabled');
            }
            active = [];
        }
    }

    if (global.document && global.document.addEventListener) {
        global.document.addEventListener('visibilitychange', function () {
            if (global.document.hidden) {
                for (var i = 0; i < active.length; i++) {
                    var st = active[i];
                    if (st && st.lastPos) {
                        st.cur = { lat: st.lastPos.lat, lng: st.lastPos.lng };
                        st.playing = false;
                        placeOnMap(st, st.cur, true);
                    }
                }
                active = [];
                return;
            }
            wake();
        });
    }

    global.GpsGlide = {
        version: '3.0',
        cfg: cfg,
        move: move,
        place: place,
        forget: forget,
        setEnabled: setEnabled,
        isOn: isOn,
        log: log,
        activeCount: function () { return active.length; },
        diagnostics: function (layer) {
            var st = states.get(layer);
            if (!st) return null;
            return {
                heading: Math.round(st.heading),
                hasHeading: st.hasHeading,
                playing: st.playing,
                pathKind: st.pathKind,
                lastFixT: st.lastFixT,
                lastFixId: st.lastFixId,
                len: Math.round(st.len),
                dur: Math.round(st.dur)
            };
        },
        setRoadEnabled: function (value) { cfg.road.enabled = !!value; },
        _validateMatch: validateMatch,
        _normaliseTrack: normaliseTrack,
        _decodePolyline: decodePolyline,
        _state: function (layer) { return states.get(layer) || null; },
        _has: function (layer) { return states.has(layer); }
    };
}(window));

# Vehicle marker smoothing + smooth map follow

**Status:** implemented, tested (28 Node assertions + 11 in-browser checks pass), zero new dependencies.
**Date:** 2026-10-01
**Engine version:** `GpsSmooth v2.0`

---

## 1. The fix in one paragraph

`Device.updateLayer()` used Leaflet's `slideTo()` plugin with a duration clamped to
`Math.max(3000, min(elapsed, 60000))`. Every poll restarted the whole animation, bursts of
positions each restarted it again, and the map was re-centred by `fitBounds()` (which snaps).
Animation now runs through a single `requestAnimationFrame` loop (`assets/js/gps-smooth.js`) that:

* learns **per device** how often it reports (`intervalEma`) and how fast it really moves (`speedEma`),
* glides the marker towards the newest fix with `duration = remaining distance / observed speed`
  (bounded by the learned cadence), never a hard-coded 3–60 s,
* **retargets mid-glide from the position the user can actually see**, so a new fix never causes a
  jump, a restart or jitter,
* collapses bursts to the newest fix, drops duplicates and stale/out-of-order fixes, and snaps
  (never glides) when the position is physically impossible,
* owns the viewport in **Follow mode**: the map is panned fractionally every frame inside a
  dead zone, so it tracks the vehicle with no lag and no snap (`setView`/`fitBounds` are never used).

---

## 2. Files changed

| File | Change |
| --- | --- |
| `public/assets/js/gps-smooth.js` | **NEW** – the glide engine (v2), ~640 lines, no dependencies |
| `public/assets/js/app.js` | `Device.updateLayer()` now calls `GpsSmooth.move()/place()`; `Device.onLayerRemove()` calls `GpsSmooth.forget()`; new `startFollow/stopFollow/toggleFollow/followControl/isFollowing` on the devices controller; `select()` starts/stops follow; **old inline GPS-SMOOTH v1 block kept but inert under `window.GpsSmoothLegacy`** so it cannot shadow v2 |
| `Tobuli/Views/Frontend/Layouts/partials/map-follow-control.blade.php` | **NEW** – the Follow toggle button (`#followDevice`, `fa-crosshairs`) |
| `Tobuli/Views/Frontend/Layouts/loged.blade.php` | loads `gps-smooth.js` **before** `app.js`; includes the Follow toggle in the map controls |
| `Tobuli/Views/Frontend/Layouts/sharing.blade.php` | same two changes (shared/public map page) |
| `Tobuli/Views/Frontend/Layouts/default.blade.php` | loads `gps-smooth.js` before `app.js` |
| `resources/assets/js/model/device.js` | mirror of the `updateLayer`/`onLayerRemove` wiring (source tree parity, in case the bundle is ever rebuilt) |
| `resources/assets/js/lib/gps-smooth.js` | identical copy of the engine (source tree parity) |
| `tests/js/gps-smooth.test.js` | **NEW** – deterministic test suite replaying every pattern of your log |
| `public/marker-harness.html` | **NEW** – interactive demo + in-browser self checks + FPS meter |

No PHP routes, controllers, models, DB or settings were touched. No CSS was added (and no CSS
transition is ever applied to a marker – Leaflet owns the transform).

---

## 3. How each pattern from your log is handled

| Pattern from your log | Engine decision (visible in `GpsSmooth.diagnostics()`) |
| --- | --- |
| 05:02–05:44, positions 1–10 s apart, gradual movement | `glide` / `retarget` – continuous, pace-matched motion |
| New fix arriving mid-glide | `retarget` – position stays continuous (0 m step), only speed/direction change |
| 05:44:52–54 duplicates (`9.71923` … `9.71923` repeated) | `skip:duplicate` – nothing moves, the learned cadence/pace is untouched |
| 05:44:58 teleports ~2 km south 1 s later (other track) | `snap:teleport` – implied speed > 250 km/h ⇒ instant jump, then normal gliding |
| 05:45:35 – 37 positions on one timestamp | one `glide` to the **newest** fix; 36 intermediates dropped, never animated |
| 05:55:21/22 – 45 s gap then catch-up burst | one glide stretched to the gap (not a burst of jumps) |
| 06:07–06:08 – same coordinates for minutes, speed 13 knots | `skip:duplicate` – identical coordinates are ignored whatever the speed field says |
| 06:29–06:31 – slow crawl, 43 s later same spot | cadence-matched slow glide, then the marker parks exactly on the last fix |
| Device offline > 5 min, then dumps a backlog | `snap:offline-reconnect` once, then smooth movement resumes |
| Device reporting only every 3 min | glide duration ≈ the 3 min cadence ⇒ **slow continuous glide**, not fast-then-freeze |
| Browser tab hidden | animation pauses; on return it snaps to the newest fix and resumes |
| Animation setting disabled (`device_move_animation`) | `snap:animation-off` – instant placement, any running glide is cancelled |
| Only one position in history | placed and stopped, no animation loop is started |

### Snap rules (in order)
1. no previous fix (first placement) · 2. tab hidden · 3. device silent for
`max(5 min, 3 × its own cadence)` · 4. implied speed > `maxPlausibleSpeed` (70 m/s ≈ 250 km/h) ·
5. same-timestamp burst jumping more than `burstJumpMeters` (1500 m) · 6. no timestamps at all and
a jump over `snapMeters` (5000 m). Everything else glides.

---

## 4. Tuning knobs (no code needed)

Open the console on the map page and adjust `GpsSmooth.cfg`, e.g.

```js
GpsSmooth.cfg.maxPlausibleSpeed = 90;      // m/s, raise if you track aircraft
GpsSmooth.cfg.minSegMs = 1200;             // calmer glides
GpsSmooth.cfg.follow.deadZoneX = 0.28;     // wider dead zone = less panning
GpsSmooth.cfg.follow.alpha = 0.10;         // softer catch-up
GpsSmooth.cfg.coast = true;                // keep creeping past the last fix (off by default)
GpsSmooth.cfg.debug = true;                // console trace of every decision
GpsSmooth.diagnostics();                   // { deviceId: { reason, interval, speed } }
GpsSmooth.stats();                         // { active, following, paused, frames }
```

**Road snapping (optional, off by default)** – enable per session or permanently:

```js
GpsSmooth.enableOsrm('https://router.project-osrm.org');   // or your own OSRM instance
GpsSmooth.disableRoad();
```
Only segments longer than `cfg.road.minMeters` (400 m) are routed; the road geometry is blended in
over 250 ms so it never pops, any failure silently falls back to the straight line. Short segments
stay straight (that is explicitly fine per your spec).

---

## 5. Backup + rollback

Backups were taken before editing (timestamp `20261001_1110`):

```
public/assets/js/app.js.bak_markersmooth_20261001_1110
resources/assets/js/model/device.js.bak_markersmooth_20261001_1110
resources/assets/js/controller/devices.js.bak_markersmooth_20261001_1110
Tobuli/Views/Frontend/Layouts/loged.blade.php.bak_markersmooth_20261001_1110
Tobuli/Views/Frontend/Layouts/sharing.blade.php.bak_markersmooth_20261001_1110
Tobuli/Views/Frontend/Layouts/default.blade.php.bak_markersmooth_20261001_1110
```

Full rollback (from the project root):

```bash
cp public/assets/js/app.js.bak_markersmooth_20261001_1110 public/assets/js/app.js
cp resources/assets/js/model/device.js.bak_markersmooth_20261001_1110 resources/assets/js/model/device.js
cp Tobuli/Views/Frontend/Layouts/loged.blade.php.bak_markersmooth_20261001_1110 Tobuli/Views/Frontend/Layouts/loged.blade.php
cp Tobuli/Views/Frontend/Layouts/sharing.blade.php.bak_markersmooth_20261001_1110 Tobuli/Views/Frontend/Layouts/sharing.blade.php
cp Tobuli/Views/Frontend/Layouts/default.blade.php.bak_markersmooth_20261001_1110 Tobuli/Views/Frontend/Layouts/default.blade.php
rm public/assets/js/gps-smooth.js resources/assets/js/lib/gps-smooth.js \
   Tobuli/Views/Frontend/Layouts/partials/map-follow-control.blade.php
php artisan view:clear && php artisan view:cache
```

Surgical rollback (keep the fix, disable the animation): turn off *Settings → Plugins →
device move animation* and the marker snaps instantly; that path is untouched by this change.

---

## 6. Verification

### 6.1 Automated (Node, no DB needed)

```bash
node tests/js/gps-smooth.test.js
# 28 checks, ALL GPS-SMOOTH CHECKS PASSED
```
It replays every pattern listed in §3 with a fake clock and asserts: no frozen frames during a
glide, duration follows the cadence, 37-fix bursts produce a single target, duplicates never move or
pollute the learned pace, teleports/offline/hidden-tab snap, stale fixes are dropped, mid-glide
retargets cause no jump and < 8 m single-frame steps, follow pans in eased steps, never `setView`,
never oscillates, and the rAF loop stops when there is nothing to animate.

### 6.2 Interactive (browser harness)

```bash
php -S 127.0.0.1:8099 -t .        # from the project root
# then open http://127.0.0.1:8099/public/marker-harness.html
```
The page loads the **real** engine file, real Leaflet 1.0.3 and OSM tiles over your log's
coordinates. It runs 11 in-browser checks on load, replays the log patterns at ×4 speed
(the engine's time/speed limits are scaled with it so behaviour matches production), shows the
engine's decision for every fix, and reports FPS/frame time live. Open it in a normal browser
window for FPS numbers – embedded preview panes throttle `requestAnimationFrame`.

### 6.3 Manual checklist on the live map

1. Select a moving device → the marker glides, the map pans smoothly behind it, the **Follow**
   toggle turns on by itself.
2. Drag the map while it follows → panning pauses and the toggle unchecks.
3. Uncheck/re-check Follow → the toggle works both ways (with nothing selected it follows the first
   visible device).
4. New fix arrives mid-glide → no visible restart or jitter.
5. Open the console: `GpsSmooth.diagnostics()` shows `glide`/`retarget`/`skip:duplicate`/`snap:*`
   per device with the learned cadence and speed.
6. Regression: device selection/highlight, popups, tail trails, cluster view, "fit objects"
   (turning fit objects on takes the viewport back from Follow).
7. Switch the animation setting off → markers snap instantly.

---

## 7. Dependencies

**None added.** No npm/bower package, no CDN script, no PHP package. The engine is a single
dependency-free file loaded from the local assets folder. Optional road snapping uses `fetch`/XHR
against an OSRM-compatible URL that you choose (off by default, no vendored library, no polyline
package – the decoder is 20 lines in the engine).

Leaflet's `Leaflet.Marker.SlideTo` plugin is still present in the bundle but no longer called by the
device marker path (only the vendored plugin definition remains).

---

## 8. Performance

Measured on this machine (Electron/Chromium renderer, real Leaflet markers on a real OSM map):

| Load | Result |
| --- | --- |
| 200 simultaneously animated markers | **60 FPS / 16.7 ms frame** |
| 500 simultaneously animated markers | **59 FPS / 17.0 ms frame** |
| Engine math only (Node, no DOM, 120 gliding markers) | **0.9–1.4 µs per marker per frame** (~0.1–0.17 ms/frame for 120) ⇒ ~12 000–19 000 markers inside a 16.7 ms budget |

How it stays cheap:

* **one** `requestAnimationFrame` loop for all markers (no per-marker timers/timeouts),
* one `marker.setLatLng()` per frame per moving marker – Leaflet then writes a single CSS transform;
* markers that have arrived, are parked, are duplicated, or have left the map are dropped from the
  active list immediately (zero cost),
* the render loop stops completely when nothing is animating and nobody is followed.

Practical guidance: the real app does more work per moved marker than the harness (its `move` event
also rebuilds the tail polyline and the inaccuracy circle), so budget ~3× the harness cost: expect
**comfortable smoothness up to ~150–200 concurrently animated markers** on typical client hardware,
and degrade gracefully beyond that (every marker still moves correctly, frames just get longer).
Clustered or hidden devices are excluded from animation automatically.

---

## 9. Caveats worth knowing

* The live poll (`objects/items_json`) returns only the **newest** position per device, so a
  server-side burst reaches the browser as one jump – that is exactly the case the snap rules cover.
  The burst/duplicate/out-of-order handling is still fully active and is exercised by the test suite
  and the harness, and it is what any future per-position feed (websocket, playback) will hit.
* Follow mode keeps the current zoom (it only pans); "fit objects" and manual zoom still work.
* The map's polling `fitBounds()` recenter is switched off while Follow is on, because that is what
  made the map snap instead of pan. Turning "fit objects" on restores it.
* `coast` (creeping past the last known fix) is **off** by default on purpose: a late fix should
  leave the marker exactly on the last reported position rather than inventing up to ~25 m of travel.

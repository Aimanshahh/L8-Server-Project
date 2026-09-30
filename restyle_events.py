#!/usr/bin/env python3
"""
Idempotent patch script for the Events tab restyle.

Modifies only the permitted files:
  - Tobuli/Views/Frontend/Objects/tabs/events.blade.php
  - Tobuli/Views/Frontend/Events/index.blade.php
  - Tobuli/Views/Frontend/Layouts/partials/head.blade.php
  - public/assets/css/theme-dark.css  (append-only)
  - public/assets/css/events-page-overrides.css  (create with light rules if missing)

Backup convention:
  <original>.bak_restyle_YYYYMMDD_HHMM

Idempotent: safe to run many times. Detects already-applied changes and
skips them instead of duplicating CSS/HTML or piling up backups.
"""

import datetime
import os
import shutil
import sys

ROOT = os.environ.get("TOBULI_ROOT", os.getcwd())

FILES = {
    "events_tab": os.path.join(ROOT, "Tobuli/Views/Frontend/Objects/tabs/events.blade.php"),
    "events_row": os.path.join(ROOT, "Tobuli/Views/Frontend/Events/index.blade.php"),
    "head": os.path.join(ROOT, "Tobuli/Views/Frontend/Layouts/partials/head.blade.php"),
    "theme_dark": os.path.join(ROOT, "public/assets/css/theme-dark.css"),
    "events_css": os.path.join(ROOT, "public/assets/css/events-page-overrides.css"),
}


def backup(path, stamp):
    bak = f"{path}.bak_restyle_{stamp}"
    if not os.path.exists(bak):
        shutil.copyfile(path, bak)
    return bak


def read(path):
    with open(path, "r", encoding="utf-8") as f:
        return f.read()


def write(path, content):
    with open(path, "w", encoding="utf-8") as f:
        f.write(content)


EVENTS_TAB_HTML = """<div class="tab-pane-header events-header">
    <div class="form">
        <div class="input-group">
            <div class="form-group search">
                {!!Form::text('search', null, ['class' => 'form-control', 'id' => 'events_search_field', 'placeholder' => trans('front.search'), 'autocomplete' => 'off'])!!}
            </div>
            <span class="input-group-btn">

                <button class="btn btn-default" type="button"  data-url="{!! \\Tobuli\\Lookups\\Tables\\EventsLookupTable::route('index') !!}" data-modal="events_lookup">
                    <i class="icon lookup"></i>
                </button>

                @if(Auth::user()->perm('events', 'remove'))
                    <button class="btn btn-default" type="button" data-url="{!!route('events.do_destroy')!!}" data-modal="events_do_destroy">
                        <i class="icon remove-all"></i>
                    </button>
                @endif
            </span>
        </div>
    </div>
    <div class="events-col-head">
        <span class="events-col-head__time">{{ trans('front.time') }}</span>
        <span class="events-col-head__object">{{ trans('front.object') }}</span>
        <span class="events-col-head__event">{{ trans('front.event') }}</span>
        <span class="events-col-head__actions"></span>
    </div>
</div>

<div class="tab-pane-body">
    <table class="table table-condensed events-table">
        <thead>
            <tr>
                <th></th>
                <th></th>
            </tr>
        </thead>

        <tbody id="ajax-events"></tbody>
    </table>
</div>"""


EVENTS_ROW_HTML = """@if (!empty($events))
    @foreach ($events as $item)
        <tr class="events-row" data-event-id="{!!$item->id!!}" onClick="app.events.select({!!$item->id!!});">
            <td class="events-cell events-cell--time">
                <span class="datetime">
                    <span class="time">{{ Formatter::date()->human($item->time) }}</span>
                    <span class="date">{{ Formatter::dtime()->human($item->time) }}</span>
                </span>
            </td>
            <td class="events-cell events-cell--object">
                <span class="device-name">{{ $item->device->name ?? '' }}</span>
            </td>
            <td class="events-cell events-cell--event">
                <span class="event-title">{{ $item->title }}</span>
                @if (settings('plugins.event_section_address.status'))
                    <span class="event-address">
                        <span data-device="address" data-lat="{{ $item->latitude }}" data-lng="{{ $item->longitude }}"></span>
                    </span>
                @endif
            </td>
            <td class="events-cell events-cell--actions">
                @if(Auth::user()->can('remove', $item))
                <div class="btn-group dropleft droparrow"  data-position="fixed">
                    <i class="btn icon options" data-toggle="dropdown" data-position="fixed" aria-haspopup="true" aria-expanded="false"></i>
                    <ul class="dropdown-menu">

                        <li>
                            <a href="javascript:;" data-url="{{ route('alerts.edit', $item->alert_id) }}" data-modal="alerts_edit">
                                <span class="icon event"></span>
                                <span class="text">{{ trans('global.alert') }}</span>
                            </a>
                        </li>


                            <li>
                                <a href="javascript:;" data-url="{{ route('events.do_destroy', ['id' => $item->id]) }}" data-modal="events_do_destroy">
                                    <span class="icon delete"></span>
                                    <span class="text">{{ trans('global.delete') }}</span>
                                </a>
                            </li>
                    </ul>
                </div>
                @endif
            </td>
            <?php
                $arr = $item->toArray();
                $arr['time'] = Formatter::time()->human($item->time);
                unset($arr['geofence'], $arr['device'], $arr['alert'], $arr['poi']);
                if (isset($item->device) ?  : '')
                    $arr['device']['name'] = $item->device->name;
                if (isset($item->geofence->name) ?  : '')
                    $arr['geofence']['name'] = $item->geofence->name;
            ?>
            <script>app.events.add({!! json_encode($arr) !!});</script>
        </tr>
    @endforeach
    @if (method_exists($events, 'nextPageUrl') && $events->nextPageUrl())
        <tr data-toggle="scroll" data-parent=".tab-pane-body" data-url="{{ $events->nextPageUrl() }}" class="events-load-more">
            <td colspan="4"></td>
        </tr>
    @endif
@else
    <tr class="events-row">
        <td class="events-cell no-data" colspan="4">{!!trans('front.no_events')!!}</td>
    </tr>
@endif"""


THEME_DARK_APPEND = """
/* ==========================================================================
   Events tab restyle (dark) — scoped under #events_tab so it does not touch
   the legacy Objects/History tables or other .table components.
   Layout: sticky header + 4 logical columns (Time | Object | Event | Actions),
   row separators, hover tint, selected accent, truncation for long names/addresses.
   ========================================================================== */

html[data-theme="dark"] #events_tab .events-header {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 12px 12px 10px;
}

html[data-theme="dark"] #events_tab .events-col-head {
    display: grid;
    grid-template-columns: 130px 1fr 1fr 42px;
    gap: 0;
    align-items: center;
    padding: 0 12px 8px;
    border-bottom: 1px solid var(--d-border-soft);
    position: sticky;
    top: 0;
    background: var(--d-bg);
    z-index: 2;
}

html[data-theme="dark"] #events_tab .events-col-head span {
    display: block;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--d-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

html[data-theme="dark"] #events_tab .events-col-head__time {
    padding-left: 4px;
}

html[data-theme="dark"] #events_tab .events-col-head__actions {
    text-align: right;
    padding-right: 4px;
}

html[data-theme="dark"] #events_tab .events-table {
    background: transparent;
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

html[data-theme="dark"] #events_tab .events-table > tbody > tr.events-row > td {
    padding: 0 12px;
    vertical-align: middle;
    border-bottom: 1px solid var(--d-border-soft);
    background: transparent;
}

html[data-theme="dark"] #events_tab .events-table > tbody > tr.events-row:last-child > td {
    border-bottom: 0;
}

html[data-theme="dark"] #events_tab .events-row > td.events-cell--time {
    position: relative;
    padding-left: 12px;
}

html[data-theme="dark"] #events_tab .events-row > td.events-cell--actions {
    position: relative;
    padding-right: 12px;
}

html[data-theme="dark"] #events_tab .events-row > td.events-cell--time::before,
html[data-theme="dark"] #events_tab .events-row > td.events-cell--object::before,
html[data-theme="dark"] #events_tab .events-row > td.events-cell--event::before {
    content: "";
    position: absolute;
    top: 0;
    bottom: 0;
    width: 1px;
    background: rgba(255, 255, 255, 0.05);
    pointer-events: none;
}

html[data-theme="dark"] #events_tab .events-row > td.events-cell--object::before {
    right: 0;
}

html[data-theme="dark"] #events_tab .events-row > td.events-cell--event::before {
    right: 0;
}

html[data-theme="dark"] #events_tab .datetime {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

html[data-theme="dark"] #events_tab .datetime .date {
    font-size: 12px;
    font-weight: 500;
    color: var(--d-text-strong);
    line-height: 1.25;
}

html[data-theme="dark"] #events_tab .datetime .time {
    font-size: 13px;
    color: var(--d-muted);
    font-variant-numeric: tabular-nums;
    line-height: 1.25;
}

html[data-theme="dark"] #events_tab .device-name {
    display: block;
    font-size: 13px;
    color: var(--d-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

html[data-theme="dark"] #events_tab .event-title {
    display: block;
    font-size: 13px;
    color: var(--d-text);
    line-height: 1.35;
}

html[data-theme="dark"] #events_tab .event-address {
    display: block;
    font-size: 11.5px;
    color: var(--d-muted);
    line-height: 1.35;
    margin-top: 3px;
    max-height: 27px;
    overflow: hidden;
    text-overflow: ellipsis;
}

html[data-theme="dark"] #events_tab .event-address::after {
    content: "";
    display: block;
    height: 10px;
}

html[data-theme="dark"] #events_tab .events-row:hover > td {
    background: rgba(59, 130, 246, 0.08);
}

html[data-theme="dark"] #events_tab .events-row.selected > td {
    background: rgba(59, 130, 246, 0.16);
}

html[data-theme="dark"] #events_tab .events-row.selected > td.events-cell--time::before,
html[data-theme="dark"] #events_tab .events-row.selected > td.events-cell--object::before,
html[data-theme="dark"] #events_tab .events-row.selected > td.events-cell--event::before {
    background: var(--d-accent);
}

html[data-theme="dark"] #events_tab .events-row > td.events-cell--actions {
    text-align: right;
}

html[data-theme="dark"] #events_tab .events-row > td.events-cell--actions .btn-group {
    float: right;
}

html[data-theme="dark"] #events_tab .events-table > tbody > tr.events-load-more {
    height: 0;
    padding: 0;
}

html[data-theme="dark"] #events_tab .events-table > tbody > tr > td.no-data {
    text-align: center;
    color: var(--d-muted);
    padding: 24px 12px;
    background: transparent;
    border-bottom: 0 !important;
}

/* keep dropdowns usable inside the narrow event column */
html[data-theme="dark"] #events_tab .btn-group.dropleft .dropdown-menu {
    left: auto;
    right: 0;
    min-width: 150px;
}

/* narrower screens: collapse vertical dividers, keep header grid readable */
@media (max-width: 640px) {
    html[data-theme="dark"] #events_tab .events-col-head {
        grid-template-columns: 1fr 1fr 1fr 36px;
        gap: 4px;
        padding: 0 8px 6px;
    }

    html[data-theme="dark"] #events_tab .events-col-head__time {
        padding-left: 0;
    }

    html[data-theme="dark"] #events_tab .events-col-head__actions {
        padding-right: 0;
    }

    html[data-theme="dark"] #events_tab .events-col-head span {
        font-size: 9px;
    }

    html[data-theme="dark"] #events_tab .events-table > tbody > tr.events-row > td {
        padding: 0 8px;
    }

    html[data-theme="dark"] #events_tab .events-row > td.events-cell--time::before,
    html[data-theme="dark"] #events_tab .events-row > td.events-cell--object::before,
    html[data-theme="dark"] #events_tab .events-row > td.events-cell--event::before {
        display: none;
    }

    html[data-theme="dark"] #events_tab .datetime .date {
        font-size: 11px;
    }

    html[data-theme="dark"] #events_tab .datetime .time {
        font-size: 12px;
    }

    html[data-theme="dark"] #events_tab .device-name,
    html[data-theme="dark"] #events_tab .event-title {
        font-size: 12px;
    }

    html[data-theme="dark"] #events_tab .event-address {
        font-size: 10.5px;
        max-height: 20px;
    }
}

/* ==========================================================================
   End Events tab restyle (dark)
   ========================================================================== */
"""


LIGHT_CSS = """/* ==========================================================================
   Events tab restyle (light) — mirrors the dark rules under #events_tab so
   the Events list reads as a clean activity log in light mode too.
   Loaded BEFORE theme-dark.css so the dark file can override these for dark.
   ========================================================================== */

html[data-theme="light"] #events_tab .events-header {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 12px 12px 10px;
}

html[data-theme="light"] #events_tab .events-col-head {
    display: grid;
    grid-template-columns: 130px 1fr 1fr 42px;
    gap: 0;
    align-items: center;
    padding: 0 12px 8px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    position: sticky;
    top: 0;
    background: #F8F9FB;
    z-index: 2;
}

html[data-theme="light"] #events_tab .events-col-head span {
    display: block;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #9CA3AF;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

html[data-theme="light"] #events_tab .events-col-head__time {
    padding-left: 4px;
}

html[data-theme="light"] #events_tab .events-col-head__actions {
    text-align: right;
    padding-right: 4px;
}

html[data-theme="light"] #events_tab .events-table {
    background: transparent;
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

html[data-theme="light"] #events_tab .events-table > tbody > tr.events-row > td {
    padding: 0 12px;
    vertical-align: middle;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    background: transparent;
}

html[data-theme="light"] #events_tab .events-table > tbody > tr.events-row:last-child > td {
    border-bottom: 0;
}

html[data-theme="light"] #events_tab .events-row > td.events-cell--time {
    position: relative;
    padding-left: 12px;
}

html[data-theme="light"] #events_tab .events-row > td.events-cell--actions {
    position: relative;
    padding-right: 12px;
}

html[data-theme="light"] #events_tab .events-row > td.events-cell--time::before,
html[data-theme="light"] #events_tab .events-row > td.events-cell--object::before,
html[data-theme="light"] #events_tab .events-row > td.events-cell--event::before {
    content: "";
    position: absolute;
    top: 0;
    bottom: 0;
    width: 1px;
    background: rgba(0, 0, 0, 0.05);
    pointer-events: none;
}

html[data-theme="light"] #events_tab .events-row > td.events-cell--object::before {
    right: 0;
}

html[data-theme="light"] #events_tab .events-row > td.events-cell--event::before {
    right: 0;
}

html[data-theme="light"] #events_tab .datetime {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

html[data-theme="light"] #events_tab .datetime .date {
    font-size: 12px;
    font-weight: 500;
    color: #111827;
    line-height: 1.25;
}

html[data-theme="light"] #events_tab .datetime .time {
    font-size: 13px;
    color: #6B7280;
    font-variant-numeric: tabular-nums;
    line-height: 1.25;
}

html[data-theme="light"] #events_tab .device-name {
    display: block;
    font-size: 13px;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

html[data-theme="light"] #events_tab .event-title {
    display: block;
    font-size: 13px;
    color: #111827;
    line-height: 1.35;
}

html[data-theme="light"] #events_tab .event-address {
    display: block;
    font-size: 11.5px;
    color: #6B7280;
    line-height: 1.35;
    margin-top: 3px;
    max-height: 27px;
    overflow: hidden;
    text-overflow: ellipsis;
}

html[data-theme="light"] #events_tab .event-address::after {
    content: "";
    display: block;
    height: 10px;
}

html[data-theme="light"] #events_tab .events-row:hover > td {
    background: rgba(37, 99, 235, 0.05);
}

html[data-theme="light"] #events_tab .events-row.selected > td {
    background: rgba(37, 99, 235, 0.12);
}

html[data-theme="light"] #events_tab .events-row.selected > td.events-cell--time::before,
html[data-theme="light"] #events_tab .events-row.selected > td.events-cell--object::before,
html[data-theme="light"] #events_tab .events-row.selected > td.events-cell--event::before {
    background: #2563EB;
}

html[data-theme="light"] #events_tab .events-row > td.events-cell--actions {
    text-align: right;
}

html[data-theme="light"] #events_tab .events-row > td.events-cell--actions .btn-group {
    float: right;
}

html[data-theme="light"] #events_tab .events-table > tbody > tr.events-load-more {
    height: 0;
    padding: 0;
}

html[data-theme="light"] #events_tab .events-table > tbody > tr > td.no-data {
    text-align: center;
    color: #6B7280;
    padding: 24px 12px;
    background: transparent;
    border-bottom: 0 !important;
}

/* keep dropdowns usable inside the narrow event column */
html[data-theme="light"] #events_tab .btn-group.dropleft .dropdown-menu {
    left: auto;
    right: 0;
    min-width: 150px;
}

/* narrower screens: collapse vertical dividers, keep header grid readable */
@media (max-width: 640px) {
    html[data-theme="light"] #events_tab .events-col-head {
        grid-template-columns: 1fr 1fr 1fr 36px;
        gap: 4px;
        padding: 0 8px 6px;
    }

    html[data-theme="light"] #events_tab .events-col-head span {
        font-size: 9px;
    }

    html[data-theme="light"] #events_tab .events-table > tbody > tr.events-row > td {
        padding: 0 8px;
    }

    html[data-theme="light"] #events_tab .events-row > td.events-cell--time::before,
    html[data-theme="light"] #events_tab .events-row > td.events-cell--object::before,
    html[data-theme="light"] #events_tab .events-row > td.events-cell--event::before {
        display: none;
    }

    html[data-theme="light"] #events_tab .datetime .date {
        font-size: 11px;
    }

    html[data-theme="light"] #events_tab .datetime .time {
        font-size: 12px;
    }

    html[data-theme="light"] #events_tab .device-name,
    html[data-theme="light"] #events_tab .event-title {
        font-size: 12px;
    }

    html[data-theme="light"] #events_tab .event-address {
        font-size: 10.5px;
        max-height: 20px;
    }
}
"""


def main():
    missing = [name for name, path in FILES.items() if name != "events_css" and not os.path.exists(path)]
    if missing:
        print(f"FAIL: missing required source files: {missing}")
        return 2

    stamp = datetime.datetime.now().strftime("%Y%m%d_%H%M")
    report = []

    # --- events.blade.php ---
    events_tab_path = FILES["events_tab"]
    original = read(events_tab_path)
    if "events-col-head" in original and 'events-table' in original and '<tbody id="ajax-events">' in original:
        report.append("events.blade.php: already restyled (skip)")
    else:
        backup(events_tab_path, stamp)
        write(events_tab_path, EVENTS_TAB_HTML)
        report.append("events.blade.php: restyled (backup created)")

    # --- Events/index.blade.php ---
    events_row_path = FILES["events_row"]
    original = read(events_row_path)
    if "events-row" in original and "events-cell--time" in original and "events-load-more" in original:
        report.append("Events/index.blade.php: already restyled (skip)")
    else:
        backup(events_row_path, stamp)
        write(events_row_path, EVENTS_ROW_HTML)
        report.append("Events/index.blade.php: restyled (backup created)")

    # --- head.blade.php ---
    head_path = FILES["head"]
    original = read(head_path)
    if "events-page-overrides.css" in original:
        report.append("head.blade.php: already patched to load events-page-overrides.css (skip)")
    else:
        backup(head_path, stamp)
        needle = "<link rel=\"stylesheet\" href=\"{{ asset_resource('assets/css/objects-page-overrides.css') }}?v=20260930-1\">\n<link rel=\"stylesheet\" href=\"{{ asset_resource('assets/css/theme-dark.css') }}?v=20260930-1\">\n@include('Frontend.Layouts.partials.theme-script')"
        replacement = "<link rel=\"stylesheet\" href=\"{{ asset_resource('assets/css/objects-page-overrides.css') }}?v=20260930-1\">\n<link rel=\"stylesheet\" href=\"{{ asset_resource('assets/css/events-page-overrides.css') }}?v=20260930-1\">\n<link rel=\"stylesheet\" href=\"{{ asset_resource('assets/css/theme-dark.css') }}?v=20260930-1\">\n@include('Frontend.Layouts.partials.theme-script')"
        if needle in original:
            write(head_path, original.replace(needle, replacement))
        else:
            # fallback: insert before theme-dark.css link
            idx = original.find("<link rel=\"stylesheet\" href=\"{{ asset_resource('assets/css/theme-dark.css')")
            if idx == -1:
                idx = original.find("theme-dark.css")
            insert = "<link rel=\"stylesheet\" href=\"{{ asset_resource('assets/css/events-page-overrides.css') }}?v=20260930-1\">\n"
            write(head_path, original[:idx] + insert + original[idx:])
        report.append("head.blade.php: patched (backup created)")

    # --- events-page-overrides.css (light) ---
    events_css_path = FILES["events_css"]
    if os.path.exists(events_css_path):
        if "html[data-theme=\"light\"] #events_tab .events-col-head" in read(events_css_path):
            report.append("events-page-overrides.css: already present (skip)")
        else:
            backup(events_css_path, stamp)
            write(events_css_path, LIGHT_CSS)
            report.append("events-page-overrides.css: replaced with restyle light rules (backup created)")
    else:
        write(events_css_path, LIGHT_CSS)
        report.append("events-page-overrides.css: created (light rules)")

    # --- theme-dark.css (append) ---
    theme_dark_path = FILES["theme_dark"]
    original = read(theme_dark_path)
    if "html[data-theme=\"dark\"] #events_tab .events-col-head" in original:
        report.append("theme-dark.css: Events rules already appended (skip)")
    else:
        backup(theme_dark_path, stamp)
        content = original.rstrip() + "\n" + THEME_DARK_APPEND
        write(theme_dark_path, content)
        report.append("theme-dark.css: Events rules appended (backup created)")

    print("\n".join(report))
    return 0


if __name__ == "__main__":
    sys.exit(main())

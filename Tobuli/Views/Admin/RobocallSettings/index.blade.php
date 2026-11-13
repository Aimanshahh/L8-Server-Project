@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-list-overrides.css') }}">
    <style>
        .rcs-card { padding: 22px; margin-bottom: 18px; }
        .rcs-card h4 { margin: 0 0 14px; font-size: 16px; font-weight: 700; }
        .rcs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; }
        .rcs-field label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .rcs-input { width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid rgba(128,128,128,.4); border-radius: 8px; background: transparent; color: inherit; font-size: 14px; }
        .rcs-muted { opacity: .7; font-size: 13px; line-height: 1.5; }
        .rcs-btn { padding: 9px 18px; border-radius: 8px; border: 1px solid rgba(128,128,128,.4); background: transparent; color: inherit; font-weight: 600; font-size: 14px; cursor: pointer; }
        .rcs-btn-primary { background: #3b82f6; border-color: #3b82f6; color: #fff; }
        .rcs-btn:disabled { opacity: .6; cursor: default; }
        .rcs-switch { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 16px; cursor: pointer; margin-bottom: 6px; }
        .rcs-switch input { width: 20px; height: 20px; }
        .rcs-badge { padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .rcs-badge.on { background: #dcfce7; color: #166534; }
        .rcs-badge.off { background: #fee2e2; color: #991b1b; }
        .rcs-msg { display: none; margin-bottom: 18px; padding: 12px 14px; border-radius: 10px; font-size: 14px; font-weight: 500; }
        .rcs-msg.ok { background: #dcfce7; color: #166534; }
        .rcs-msg.err { background: #fee2e2; color: #991b1b; }
        #rcs-test-out { display: none; margin-top: 12px; padding: 12px; border: 1px solid rgba(128,128,128,.4); border-radius: 8px; font-size: 12px; white-space: pre-wrap; word-break: break-all; }
        .rcs-card ol { margin: 0; padding-left: 20px; font-size: 14px; line-height: 1.8; }
    </style>
@stop

@section('content')
<div class="al-page">

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-phone"></i></div>
            <div>
                <h1 class="al-page__title">Robocall settings</h1>
                <p class="al-page__subtitle">Global switch and provider API. Changes apply immediately; no restart needed.</p>
            </div>
        </div>
    </div>

    <div id="rcs-msg" class="rcs-msg"></div>

    <div class="al-card rcs-card">
        <label class="rcs-switch">
            <input type="checkbox" id="rcs-enabled" {{ $s['enabled'] ? 'checked' : '' }}>
            <span>Enable Robocall (global)</span>
            <span class="rcs-badge {{ $s['enabled'] ? 'on' : 'off' }}">{{ $s['enabled'] ? 'ON' : 'OFF' }}</span>
        </label>
        <div class="rcs-muted">When off, no robocalls are placed for any client, including pending fallback calls.</div>
    </div>

    <div class="al-card rcs-card">
        <h4>Provider</h4>
        <div class="rcs-grid">
            <div class="rcs-field">
                <label for="rcs-api_name">Provider name</label>
                <input class="rcs-input" id="rcs-api_name" type="text" value="{{ $s['api_name'] }}" maxlength="45">
            </div>
            <div class="rcs-field">
                <label for="rcs-amount">Call amount</label>
                <input class="rcs-input" id="rcs-amount" type="number" min="1" value="{{ $s['amount'] }}">
            </div>
        </div>
        <div class="rcs-field" style="margin-top:14px;">
            <label for="rcs-api_url">API URL (https only)</label>
            <input class="rcs-input" id="rcs-api_url" type="text" value="{{ $s['api_url'] }}" style="font-family:monospace;">
        </div>
        <div class="rcs-field" style="margin-top:14px;">
            <label for="rcs-api_key">API key</label>
            <input class="rcs-input" id="rcs-api_key" type="password" autocomplete="new-password" placeholder="Leave blank to keep the current key" style="font-family:monospace;">
            <div class="rcs-muted" style="margin-top:6px;">
                @if ($s['key_source'] === 'database')
                    A key is stored here (encrypted). It is never shown again; enter a new one only to replace it.
                @elseif ($s['key_source'] === 'env')
                    Currently using the key from the server's .env file. Enter a key here to store it (encrypted) in the database instead.
                @else
                    No API key is configured. Calls cannot be placed until one is set.
                @endif
            </div>
        </div>
        <div class="rcs-field" style="margin-top:14px;">
            <label>Webhook URL (set this in your provider's panel)</label>
            <input class="rcs-input" type="text" readonly value="{{ $s['webhook_url'] }}" style="font-family:monospace;">
        </div>
    </div>

    <div class="al-card rcs-card">
        <h4>Behaviour</h4>
        <div class="rcs-grid">
            <div class="rcs-field">
                <label for="rcs-fallback_minutes">Fallback delay (minutes)</label>
                <input class="rcs-input" id="rcs-fallback_minutes" type="number" min="1" max="60" value="{{ $s['fallback_minutes'] }}">
            </div>
            <div class="rcs-field">
                <label for="rcs-cooldown_hours">Cooldown per device, alert and number (hours)</label>
                <input class="rcs-input" id="rcs-cooldown_hours" type="number" min="1" max="720" value="{{ $s['cooldown_hours'] }}">
            </div>
            <div class="rcs-field">
                <label for="rcs-geofence_cooldown_hours">Geofence alert cooldown (hours)</label>
                <input class="rcs-input" id="rcs-geofence_cooldown_hours" type="number" min="1" max="720" value="{{ $s['geofence_cooldown_hours'] }}">
            </div>
        </div>
    </div>

    <div class="al-card rcs-card">
        <h4>Status words from the provider's webhook</h4>
        <div class="rcs-muted" style="margin-bottom:12px;">Comma-separated, lowercase. They must match, letter for letter, the words your provider sends. The secondary-number fallback depends on them.</div>
        <div class="rcs-field">
            <label for="rcs-answered_statuses">Answered</label>
            <input class="rcs-input" id="rcs-answered_statuses" type="text" value="{{ $s['answered_statuses'] }}">
        </div>
        <div class="rcs-field" style="margin-top:14px;">
            <label for="rcs-not_reached_statuses">Not reached (triggers the fallback)</label>
            <input class="rcs-input" id="rcs-not_reached_statuses" type="text" value="{{ $s['not_reached_statuses'] }}">
        </div>
    </div>

    <div style="margin-bottom:18px;">
        <button class="rcs-btn rcs-btn-primary" id="rcs-save" type="button">Save settings</button>
    </div>

    <div class="al-card rcs-card">
        <h4>Send a test call</h4>
        <div class="rcs-muted" style="margin-bottom:12px;">Places a <strong>real call</strong> using the saved settings, so save first. Use it to check a new provider before enabling it.</div>
        <div class="rcs-grid">
            <div class="rcs-field">
                <label for="rcs-test-number">Number</label>
                <input class="rcs-input" id="rcs-test-number" type="text" placeholder="03XXXXXXXXX">
            </div>
            <div class="rcs-field">
                <label for="rcs-test-voice">Voice ID</label>
                <input class="rcs-input" id="rcs-test-voice" type="text" value="35">
            </div>
        </div>
        <div style="margin-top:14px;"><button class="rcs-btn" id="rcs-test" type="button">Send test call</button></div>
        <pre id="rcs-test-out"></pre>
    </div>

    <div class="al-card rcs-card">
        <h4>Switching to a new provider</h4>
        <ol>
            <li>Enter the new API URL and key above and save.</li>
            <li>Set the provider's webhook to the Webhook URL shown above.</li>
            <li>Update the status words to the words the new provider sends.</li>
            <li>Send a test call and check it rings and the result shows on the dashboard.</li>
            <li>Turn the global switch ON.</li>
        </ol>
        <div class="rcs-muted" style="margin-top:10px;">A provider whose request or response format differs from the current one may still need a code change.</div>
    </div>
</div>
@stop

@section('javascript')
    <script>
        (function () {
            var token = '{{ csrf_token() }}';
            var initialEnabled = {{ $s['enabled'] ? 'true' : 'false' }};
            var msg = document.getElementById('rcs-msg');

            function show(text, ok) {
                msg.className = 'rcs-msg ' + (ok ? 'ok' : 'err');
                msg.textContent = text;
                msg.style.display = 'block';
                window.scrollTo(0, 0);
            }

            function post(url, data) {
                var body = new URLSearchParams();
                Object.keys(data).forEach(function (k) { body.append(k, data[k]); });

                return fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: body.toString()
                }).then(function (r) {
                    return r.text().then(function (t) {
                        try { return JSON.parse(t); }
                        catch (e) { return { ok: false, errors: ['Request failed (HTTP ' + r.status + ').'] }; }
                    });
                });
            }

            function val(id) { return document.getElementById(id).value; }

            document.getElementById('rcs-save').addEventListener('click', function () {
                var enabled = document.getElementById('rcs-enabled').checked;

                if (enabled !== initialEnabled) {
                    var q = enabled
                        ? 'Turn Robocall ON for the whole server? Calls will be placed for every enabled client with an armed alert.'
                        : 'Turn Robocall OFF for the whole server? No calls will be placed, including pending fallback calls.';
                    if (!confirm(q)) { return; }
                }

                var btn = this;
                btn.disabled = true;

                post('{{ route('admin.robocall_settings.store') }}', {
                    enabled: enabled ? 1 : 0,
                    api_name: val('rcs-api_name'),
                    api_url: val('rcs-api_url'),
                    api_key: val('rcs-api_key'),
                    amount: val('rcs-amount'),
                    fallback_minutes: val('rcs-fallback_minutes'),
                    cooldown_hours: val('rcs-cooldown_hours'),
                    geofence_cooldown_hours: val('rcs-geofence_cooldown_hours'),
                    answered_statuses: val('rcs-answered_statuses'),
                    not_reached_statuses: val('rcs-not_reached_statuses')
                }).then(function (r) {
                    if (r.ok) {
                        show('Saved.', true);
                        setTimeout(function () { location.reload(); }, 800);
                    } else {
                        show((r.errors || ['Save failed.']).join(' '), false);
                        btn.disabled = false;
                    }
                }).catch(function () {
                    show('Save failed (network error).', false);
                    btn.disabled = false;
                });
            });

            document.getElementById('rcs-test').addEventListener('click', function () {
                var number = val('rcs-test-number');

                if (!confirm('This places a REAL call to ' + number + '. Continue?')) { return; }

                var btn = this;
                var out = document.getElementById('rcs-test-out');
                btn.disabled = true;
                btn.textContent = 'Calling...';
                out.style.display = 'none';

                post('{{ route('admin.robocall_settings.test_call') }}', {
                    number: number,
                    voice_id: val('rcs-test-voice')
                }).then(function (r) {
                    out.textContent = r.ok ? ('HTTP ' + r.http_status + '\n' + r.body) : (r.errors || ['Test call failed.']).join(' ');
                    out.style.display = 'block';
                }).catch(function () {
                    out.textContent = 'Test call failed (network error).';
                    out.style.display = 'block';
                }).then(function () {
                    btn.disabled = false;
                    btn.textContent = 'Send test call';
                });
            });
        })();
    </script>
@stop

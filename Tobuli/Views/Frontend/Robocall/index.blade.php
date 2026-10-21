@extends('Frontend.Layouts.default')

@section('header-menu-items')
    <li class="nav__item"><a class="nav__link" href="{!! route('objects.index') !!}" role="button"><span class="icon map"></span><span class="nav__link-txt">{!! trans('admin.map') !!}</span></a></li>
    <li class="nav__item"><a class="nav__link" href="{!! route('robocall.index') !!}" role="button"><span class="icon call_action"></span><span class="nav__link-txt">Robocall</span></a></li>
    @if (isAdmin())
        <li class="nav__item"><a class="nav__link" href="{!! route('admin') !!}" role="button"><span class="icon admin"></span><span class="nav__link-txt">{!! trans('global.admin') !!}</span></a></li>
    @endif
    <li class="nav__item"><a class="nav__link" href="javascript:" data-url="{!! route('my_account_settings.edit') !!}" data-modal="my_account_settings_edit"><span class="icon setup"></span><span class="nav__link-txt">Setup</span></a></li>
@stop

@section('content')
    <style>
        #robocall-page { max-width: 1200px; margin: 0 auto; padding: 16px 16px 40px; }
        #robocall-page h3 { margin: 0 0 4px; font-size: 22px; font-weight: 600; }
        #robocall-page .rc-muted { color: #6b7280; font-size: 13px; }
        #robocall-page .rc-url-box { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 10px; }
        #robocall-page .rc-url { flex: 1 1 420px; min-width: 260px; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px;
                                 background: #f9fafb; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 14px; }
        #robocall-page .rc-steps { margin: 14px 0 0; padding-left: 20px; }
        #robocall-page .rc-steps li { margin-bottom: 5px; font-size: 13.5px; }
        #robocall-page .rc-stats { display: flex; gap: 10px; flex-wrap: wrap; margin: 16px 0; }
        #robocall-page .rc-stat { flex: 0 0 auto; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; }
        #robocall-page .rc-stat b { display: block; font-size: 20px; line-height: 1.2; }
        #robocall-page .rc-stat span { font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
        #robocall-page pre { margin: 0; padding: 10px; background: #0f172a; color: #e2e8f0; border-radius: 8px;
                             max-height: 260px; overflow: auto; font-size: 12px; white-space: pre-wrap; word-break: break-word; }
        #robocall-page .rc-call-head { cursor: pointer; }
        #robocall-page .rc-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; }
    </style>

    <div id="robocall-page">
        <h3>Robocall webhook</h3>
        <div class="rc-muted">Give this URL to the robocall provider — every answer / call event it sends is dropped here on the server.</div>

        <div class="rc-url-box">
            <input class="rc-url" id="rc-webhook-url" type="text" readonly value="{{ $webhookUrl }}">
            <button class="btn btn-primary" type="button" id="rc-copy">Copy URL</button>
            <button class="btn btn-default" type="button" id="rc-test">Send test payload</button>
        </div>

        <ol class="rc-steps">
            <li><b>Method:</b> accepts <code>POST</code> (JSON or form-encoded). A plain <code>GET</code> also answers <code>200 OK</code>, so the provider can verify the URL.</li>
            <li><b>No auth / no CSRF token</b> is required — safe to paste as-is into the robocall panel.</li>
            <li>Expected back: <code>{"status":"ok","received_at":"..."}</code></li>
        </ol>

        @unless ($storageWritable)
            <div class="alert alert-danger" style="margin-top:14px">
                <b>Storage is not writable.</b> Payloads cannot be saved to <code>{{ $storagePath }}</code>.
                The webhook will answer <code>500</code> so the provider retries — fix the permissions on the
                <code>storage/app</code> directory (the web-server user needs write access) and calls will start being stored.
            </div>
        @endunless

        <div class="rc-stats">
            <div class="rc-stat"><b id="rc-total">{{ $total }}</b><span>Payloads received</span></div>
            <div class="rc-stat"><b>{{ $calls ? $calls[0]['received_at'] ?? '—' : '—' }}</b><span>Last received</span></div>
            <div class="rc-stat"><b style="font-size:13px;word-break:break-all">{{ $storagePath }}</b><span>Saved to</span></div>
        </div>

        <div class="panel panel-default">
            <div class="panel-body">
                <div class="rc-url-box" style="margin:0 0 12px">
                    <div class="rc-muted" style="flex:1 1 auto">Showing the latest {{ $pageRows }} payloads</div>
                    <label class="rc-muted" style="display:flex;align-items:center;gap:6px">
                        <input type="checkbox" id="rc-auto"> auto-refresh
                    </label>
                    <a class="btn btn-default" href="javascript:location.reload()">Refresh</a>
                    <a class="btn btn-default" href="{{ route('robocall.download') }}">Download all</a>
                    <form method="POST" action="{{ route('robocall.clear') }}" style="display:inline"
                          onsubmit="return confirm('Delete all received robocall payloads?');">
                        {!! csrf_field() !!}
                        <button class="btn btn-default" type="submit">Clear log</button>
                    </form>
                </div>

                <table class="table table-condensed" id="rc-table">
                    <thead>
                    <tr>
                        <th style="width:150px">Received</th>
                        <th style="width:120px">Event</th>
                        <th>From</th>
                        <th>To</th>
                        <th style="width:80px">Duration</th>
                        <th style="width:130px">Call ID</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($calls as $i => $call)
                        @php($payload = $call['payload'] ?? null)
                        <tr class="rc-call-head" data-toggle="collapse" data-target="#rc-call-{{ $i }}">
                            <td>{{ $call['received_at'] ?? '—' }}</td>
                            <td>{{ $call['event'] ?? '—' }}</td>
                            <td>{{ $call['from'] ?? '—' }}</td>
                            <td>{{ $call['to'] ?? '—' }}</td>
                            <td>{{ $call['duration'] ?? '—' }}</td>
                            <td class="rc-badge">{{ \Illuminate\Support\Str::limit($call['call_id'] ?? '—', 16) }}</td>
                        </tr>
                        <tr class="collapse" id="rc-call-{{ $i }}">
                            <td colspan="6">
                                <div class="rc-muted" style="margin-bottom:6px">
                                    {{ $call['method'] ?? '' }} · {{ $call['ip'] ?? '' }} · {{ $call['content_type'] ?? '' }}
                                    @if (!empty($call['recording']))
                                        · <a href="{{ $call['recording'] }}" target="_blank" rel="noopener">recording</a>
                                    @endif
                                </div>
                                <pre>{{ is_array($payload) ? json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ($call['raw'] ?? '') }}</pre>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="rc-muted" style="padding:18px;text-align:center">
                                Nothing received yet. Copy the URL above into the robocall panel, or hit <b>Send test payload</b>.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    <script>
        $(document).ready(function () {
            $('#rc-copy').on('click', function () {
                var el = document.getElementById('rc-webhook-url');
                el.select();
                el.setSelectionRange(0, 99999);
                try {
                    navigator.clipboard.writeText(el.value);
                } catch (e) {
                    document.execCommand('copy');
                }
                $(this).text('Copied');
                var btn = $(this);
                setTimeout(function () { btn.text('Copy URL'); }, 1500);
            });

            // Posts a sample payload straight to the webhook so we can confirm
            // the round trip end to end without waiting for the provider.
            $('#rc-test').on('click', function () {
                var btn = $(this);
                btn.prop('disabled', true).text('Sending...');
                $.ajax({
                    type: 'POST',
                    url: $('#rc-webhook-url').val(),
                    contentType: 'application/json',
                    dataType: 'json',
                    data: JSON.stringify({
                        event: 'call_completed',
                        call_id: 'TEST-' + Date.now(),
                        from: '+920000000000',
                        to: '+920000000001',
                        duration: 42,
                        recording_url: 'https://example.com/test-recording.mp3',
                        note: 'test payload from the Robocall page'
                    })
                }).always(function () {
                    setTimeout(function () { location.reload(); }, 400);
                });
            });

            var timer = null;
            $('#rc-auto').on('change', function () {
                if (this.checked) {
                    timer = setInterval(function () { location.reload(); }, 10000);
                } else {
                    clearInterval(timer);
                }
            });
        });
    </script>

    @include('Frontend.Layouts.partials.app')
@stop

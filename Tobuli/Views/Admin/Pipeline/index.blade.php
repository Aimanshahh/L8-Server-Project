@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-pipeline-overrides.css') }}?v=20260925-1">
@stop

@section('content')
    @php
        $queue = $data['queue'];
        $throughput = $data['throughput'];
        $unregistered = $data['unregistered'];
        $devices = $data['devices'];
        $health = $data['health'];
        $barMax = max(1, $throughput['max']);
        $perMinute = $throughput['per_minute'];
        $healthIcon = ['ok' => 'fa-circle-check', 'warning' => 'fa-triangle-exclamation', 'critical' => 'fa-circle-xmark'];
    @endphp

    <div class="al-page pl-page" id="pl-page">
        <div class="al-page__header">
            <div class="al-page__title-group">
                <div class="al-page__title-icon"><i class="fas fa-heart-pulse"></i></div>
                <div>
                    <h1 class="al-page__title">Position pipeline</h1>
                    <p class="al-page__subtitle">Live health of the Traccar &rarr; queue &rarr; insert:run &rarr; positions flow</p>
                </div>
            </div>
            <div class="al-page__actions">
                <span class="pl-health pl-health--{{ $health['level'] }}" id="pl-health-badge">
                    <i class="fas {{ $healthIcon[$health['level']] }}" id="pl-health-icon"></i>
                    <span id="pl-health-label">{{ ucfirst($health['level']) }}</span>
                </span>
                <button type="button" class="pl-btn" id="pl-refresh"><i class="fas fa-rotate"></i> Refresh</button>
            </div>
        </div>

        <div class="pl-stats">
            <div class="pl-stat">
                <div class="pl-stat__icon pl-stat__icon--violet"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="pl-stat__label">Queued positions</div>
                    <div class="pl-stat__value" data-metric="queue_depth">{{ number_format($queue['depth']) }}</div>
                    <div class="pl-stat__sub" data-metric="queue_keys">{{ $queue['keys'] }} device list(s)</div>
                </div>
            </div>
            <div class="pl-stat">
                <div class="pl-stat__icon pl-stat__icon--blue"><i class="fas fa-gauge-high"></i></div>
                <div>
                    <div class="pl-stat__label">Throughput</div>
                    <div class="pl-stat__value"><span data-metric="rate">{{ $throughput['rate'] }}</span><span class="pl-stat__unit">/min</span></div>
                    <div class="pl-stat__sub" data-metric="last_5m">{{ number_format($throughput['last_5m']) }} in last 5 min</div>
                </div>
            </div>
            <div class="pl-stat">
                <div class="pl-stat__icon pl-stat__icon--teal"><i class="fas fa-database"></i></div>
                <div>
                    <div class="pl-stat__label">Written (60 min)</div>
                    <div class="pl-stat__value" data-metric="last_60m">{{ number_format($throughput['last_60m']) }}</div>
                    <div class="pl-stat__sub" data-metric="tables">{{ $throughput['tables'] }} position table(s)</div>
                </div>
            </div>
            <div class="pl-stat">
                <div class="pl-stat__icon pl-stat__icon--green"><i class="fas fa-satellite-dish"></i></div>
                <div>
                    <div class="pl-stat__label">Devices online</div>
                    <div class="pl-stat__value" data-metric="online">{{ number_format($devices['online']) }}<span class="pl-stat__unit">/{{ $devices['total'] }}</span></div>
                    <div class="pl-stat__sub" data-metric="never">{{ $devices['never'] }} never reported</div>
                </div>
            </div>
            <div class="pl-stat">
                <div class="pl-stat__icon pl-stat__icon--orange"><i class="fas fa-user-secret"></i></div>
                <div>
                    <div class="pl-stat__label">Unknown IMEIs today</div>
                    <div class="pl-stat__value" data-metric="unregistered_today">{{ number_format($unregistered['today']) }}</div>
                    <div class="pl-stat__sub" data-metric="unregistered_rows">{{ number_format($unregistered['rows']) }} total logged</div>
                </div>
            </div>
        </div>

        <div class="pl-grid">
            <div class="al-card pl-card">
                <div class="pl-card__head">
                    <h2 class="pl-card__title"><i class="fas fa-chart-column"></i> Positions written per minute</h2>
                    <span class="pl-card__hint">last {{ \App\Http\Controllers\Admin\PipelineController::CHART_WINDOW_MINUTES }} minutes</span>
                </div>
                <div class="pl-card__body">
                    <div class="pl-bars" id="pl-bars">
                        @foreach ($perMinute as $point)
                            <div class="pl-bars__col" title="{{ $point['label'] }} &middot; {{ $point['count'] }}">
                                <div class="pl-bars__bar" style="height: {{ $point['count'] > 0 ? max(4, (int) round($point['count'] / $barMax * 100)) : 2 }}%"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="pl-bars__axis">
                        <span>{{ $perMinute[0]['label'] ?? '' }}</span>
                        <span>{{ end($perMinute)['label'] ?? '' }}</span>
                    </div>
                </div>
            </div>

            <div class="al-card pl-card">
                <div class="pl-card__head">
                    <h2 class="pl-card__title"><i class="fas fa-stethoscope"></i> Health</h2>
                </div>
                <div class="pl-card__body">
                    <ul class="pl-issues" id="pl-issues">
                        @forelse ($health['issues'] as $issue)
                            <li><i class="fas fa-circle-info"></i><span>{{ $issue }}</span></li>
                        @empty
                            <li class="pl-issues__empty"><i class="fas fa-circle-check"></i><span>All position sources are flowing normally.</span></li>
                        @endforelse
                    </ul>
                    <div class="pl-health-meta">
                        <div><span class="pl-health-meta__k">insert:run locks</span><span class="pl-health-meta__v" data-metric="locks">{{ $queue['locks'] }}</span></div>
                        <div><span class="pl-health-meta__k">worker processes</span><span class="pl-health-meta__v" data-metric="workers">{{ $queue['workers'] }}</span></div>
                        <div><span class="pl-health-meta__k">online window</span><span class="pl-health-meta__v">{{ round($devices['timeout'] / 60) }} min</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pl-grid pl-grid--two">
            <div class="al-card pl-card">
                <div class="pl-card__head">
                    <h2 class="pl-card__title"><i class="fas fa-layer-group"></i> Redis queue</h2>
                    @if ( ! $queue['available'])
                        <span class="pl-chip pl-chip--bad">unavailable</span>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-list">
                        <thead>
                        <tr>
                            <th>Device</th>
                            <th>IMEI</th>
                            <th class="pl-num">Pending</th>
                        </tr>
                        </thead>
                        <tbody id="pl-queue">
                        @forelse ($queue['top'] as $row)
                            <tr>
                                <td>{{ $row['name'] ?: '— unknown —' }}</td>
                                <td><span class="pl-code">{{ $row['imei'] }}</span></td>
                                <td class="pl-num"><span class="pl-count">{{ number_format($row['depth']) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="pl-empty">
                                @if ($queue['available'])
                                    Queue is empty — every received position has been processed.
                                @else
                                    {{ $queue['error'] ?: 'Redis is unavailable.' }}
                                @endif
                            </td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="al-card pl-card">
                <div class="pl-card__head">
                    <h2 class="pl-card__title"><i class="fas fa-user-secret"></i> Unregistered devices</h2>
                    <a class="pl-card__link" href="{{ route('admin.unregistered_devices_log.index') }}">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-list">
                        <thead>
                        <tr>
                            <th>IMEI</th>
                            <th>Port</th>
                            <th>IP</th>
                            <th>Last seen</th>
                            <th class="pl-num">Tries</th>
                        </tr>
                        </thead>
                        <tbody id="pl-unregistered">
                        @forelse ($unregistered['recent'] as $row)
                            <tr>
                                <td><span class="pl-code">{{ $row->imei }}</span></td>
                                <td>{{ $row->port }}</td>
                                <td><span class="pl-muted">{{ $row->ip ?: '—' }}</span></td>
                                <td>{{ $row->date ? Formatter::time()->human($row->date) : '—' }}</td>
                                <td class="pl-num">{{ number_format($row->times) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="pl-empty">No unknown devices have tried to connect.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="al-card pl-card">
            <div class="pl-card__head">
                <h2 class="pl-card__title"><i class="fas fa-location-dot"></i> Recent device positions</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-list">
                    <thead>
                    <tr>
                        <th>Device</th>
                        <th>IMEI</th>
                        <th>Status</th>
                        <th>Last position</th>
                        <th>Age</th>
                        <th>Protocol</th>
                        <th class="pl-num">Speed</th>
                        <th>Address</th>
                    </tr>
                    </thead>
                    <tbody id="pl-recent">
                    @forelse ($data['recent'] as $row)
                        <tr>
                            <td class="pl-strong">{{ $row['name'] }}</td>
                            <td><span class="pl-code">{{ $row['imei'] }}</span></td>
                            <td>
                                @if ($row['online'])
                                    <span class="pl-pill pl-pill--on">Online</span>
                                @else
                                    <span class="pl-pill pl-pill--off">Offline</span>
                                @endif
                            </td>
                            <td>{{ $row['server_time'] ? Formatter::time()->human($row['server_time']) : '—' }}</td>
                            <td><span class="pl-muted">{{ $row['age_human'] ?: '—' }}</span></td>
                            <td><span class="pl-chip">{{ $row['protocol'] ?: '—' }}</span></td>
                            <td class="pl-num">{{ $row['speed'] !== null ? number_format((float)$row['speed'], 1) : '—' }}</td>
                            <td class="pl-muted pl-address">{{ $row['address'] ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="pl-empty">No device has reported a position yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pl-foot">
            <i class="fas fa-clock"></i> Updated <span id="pl-updated">{{ $data['generated_at'] }}</span>
            <span class="pl-foot__sep">·</span>
            Redis {{ $queue['available'] ? 'connected' : 'unavailable' }}
        </div>
    </div>
@stop

@section('javascript')
    <script>
        (function () {
            var url = '{{ route('admin.pipeline.data') }}';
            var $page = $('#pl-page');

            function esc(value) {
                return String(value === null || value === undefined ? '' : value)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            }

            function num(value) {
                return Number(value || 0).toLocaleString();
            }

            function setMetric(name, value) {
                $page.find('[data-metric="' + name + '"]').text(value);
            }

            function render(data) {
                setMetric('queue_depth', num(data.queue.depth));
                setMetric('queue_keys', data.queue.keys + ' device list(s)');
                setMetric('rate', data.throughput.rate);
                setMetric('last_5m', num(data.throughput.last_5m) + ' in last 5 min');
                setMetric('last_60m', num(data.throughput.last_60m));
                setMetric('tables', data.throughput.tables + ' position table(s)');
                setMetric('online', num(data.devices.online));
                setMetric('never', data.devices.never + ' never reported');
                setMetric('unregistered_today', num(data.unregistered.today));
                setMetric('unregistered_rows', num(data.unregistered.rows) + ' total logged');
                setMetric('locks', data.queue.locks);
                setMetric('workers', data.queue.workers);
                $('#pl-updated').text(data.generated_at);

                var icons = {ok: 'fa-circle-check', warning: 'fa-triangle-exclamation', critical: 'fa-circle-xmark'};
                $('#pl-health-badge')
                    .removeClass('pl-health--ok pl-health--warning pl-health--critical')
                    .addClass('pl-health--' + data.health.level);
                $('#pl-health-icon').attr('class', 'fas ' + (icons[data.health.level] || icons.ok));
                $('#pl-health-label').text(data.health.level.charAt(0).toUpperCase() + data.health.level.slice(1));

                if (data.health.issues.length) {
                    $('#pl-issues').html(data.health.issues.map(function (issue) {
                        return '<li><i class="fas fa-circle-info"></i><span>' + esc(issue) + '</span></li>';
                    }).join(''));
                } else {
                    $('#pl-issues').html('<li class="pl-issues__empty"><i class="fas fa-circle-check"></i><span>All position sources are flowing normally.</span></li>');
                }

                var max = Math.max(1, data.throughput.max);
                $('#pl-bars').html(data.throughput.per_minute.map(function (point) {
                    var height = point.count > 0 ? Math.max(4, Math.round(point.count / max * 100)) : 2;
                    return '<div class="pl-bars__col" title="' + esc(point.label) + ' · ' + point.count + '">'
                        + '<div class="pl-bars__bar" style="height:' + height + '%"></div></div>';
                }).join(''));

                if (data.queue.top.length) {
                    $('#pl-queue').html(data.queue.top.map(function (row) {
                        return '<tr><td>' + esc(row.name || '— unknown —') + '</td>'
                            + '<td><span class="pl-code">' + esc(row.imei) + '</span></td>'
                            + '<td class="pl-num"><span class="pl-count">' + num(row.depth) + '</span></td></tr>';
                    }).join(''));
                } else {
                    $('#pl-queue').html('<tr><td colspan="3" class="pl-empty">' + (data.queue.available ? 'Queue is empty — every received position has been processed.' : esc(data.queue.error || 'Redis is unavailable.')) + '</td></tr>');
                }

                if (data.unregistered.recent.length) {
                    $('#pl-unregistered').html(data.unregistered.recent.map(function (row) {
                        return '<tr><td><span class="pl-code">' + esc(row.imei) + '</span></td>'
                            + '<td>' + esc(row.port) + '</td>'
                            + '<td><span class="pl-muted">' + esc(row.ip || '—') + '</span></td>'
                            + '<td>' + esc(row.date || '—') + '</td>'
                            + '<td class="pl-num">' + num(row.times) + '</td></tr>';
                    }).join(''));
                } else {
                    $('#pl-unregistered').html('<tr><td colspan="5" class="pl-empty">No unknown devices have tried to connect.</td></tr>');
                }

                if (data.recent.length) {
                    $('#pl-recent').html(data.recent.map(function (row) {
                        var pill = row.online ? '<span class="pl-pill pl-pill--on">Online</span>' : '<span class="pl-pill pl-pill--off">Offline</span>';
                        return '<tr><td class="pl-strong">' + esc(row.name) + '</td>'
                            + '<td><span class="pl-code">' + esc(row.imei) + '</span></td>'
                            + '<td>' + pill + '</td>'
                            + '<td>' + esc(row.server_time || '—') + '</td>'
                            + '<td><span class="pl-muted">' + esc(row.age_human || '—') + '</span></td>'
                            + '<td><span class="pl-chip">' + esc(row.protocol || '—') + '</span></td>'
                            + '<td class="pl-num">' + (row.speed === null ? '—' : Number(row.speed).toFixed(1)) + '</td>'
                            + '<td class="pl-muted pl-address">' + esc(row.address || '—') + '</td></tr>';
                    }).join(''));
                } else {
                    $('#pl-recent').html('<tr><td colspan="8" class="pl-empty">No device has reported a position yet.</td></tr>');
                }
            }

            function refresh() {
                $page.addClass('pl-page--loading');
                $.getJSON(url).done(render).always(function () {
                    $page.removeClass('pl-page--loading');
                });
            }

            $('#pl-refresh').on('click', refresh);
            setInterval(refresh, 20000);
        })();
    </script>
@stop

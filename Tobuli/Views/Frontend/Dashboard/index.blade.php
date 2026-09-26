@extends('Frontend.Layouts.default')

@section('header-menu-items')
<li class="nav__item"><a class="nav__link" href="{!! route('objects.index') !!}" role="button"><span class="icon map"></span><span class="nav__link-txt">{!! trans('admin.map') !!}</span></a></li>
      @if (isAdmin())
      <li class="nav__item"><a class="nav__link" href="{!!route('admin')!!}" role="button"><span class="icon admin"></span><span class="nav__link-txt">{!!trans('global.admin')!!}</span></a></li>
      @endif
    <li class="nav__item"><a class="nav__link" href="javascript:" data-url="{!!route('my_account_settings.edit')!!}" data-modal="my_account_settings_edit"><span class="icon setup"></span><span class="nav__link-txt">Setup</span></a></li>

@stop

@section('content')
    <div id="dashboard" class="dashboard-page">
        {{-- Summary cards row --}}
        <div class="dash-cards-row">
            @foreach ($statusCards as $card)
                <a class="dash-card" href="{!! route('objects.index') !!}" target="_blank" style="--accent: {{ $card['color'] }}">
                    <span class="dash-card__icon"><i class="fas {{ $card['icon'] ?? 'fa-car' }}"></i></span>
                    <span class="dash-card__label">{{ $card['label'] }}</span>
                    <span class="dash-card__value">{{ $card['value'] }}</span>

                    @if (isset($card['legend']))
                        <span class="dash-card__legend">
                            @foreach ($card['legend'] as $lg)
                                <span class="dash-card__legend-item"><i style="background: {{ $lg[2] }}"></i>{{ $lg[0] }} {{ $lg[1] }}</span>
                            @endforeach
                        </span>
                        <span class="dash-card__sub">&nbsp;</span>
                    @elseif (isset($card['pct']))
                        <span class="dash-card__bar"><span style="width: {{ $card['pct'] }}%; background: {{ $card['color'] }}"></span></span>
                        <span class="dash-card__sub">{{ $card['pct'] }}% of total</span>
                    @else
                        <span class="dash-card__sub">Requires attention</span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Middle row: live map + status donut --}}
        <div class="dash-main-row">
            <div class="dash-panel dash-panel--map">
                <div class="dash-panel__head">
                    <span class="dash-panel__title"><i class="icon map"></i> Live Fleet Map</span>
                    <div class="dash-map-filters" id="dashMapFilters">
                        <button type="button" class="active" data-filter="all">All</button>
                        <button type="button" data-filter="moving">Moving</button>
                        <button type="button" data-filter="idle">Idle</button>
                        <button type="button" data-filter="offline">Offline</button>
                    </div>
                </div>
                <div id="dashMap"></div>
            </div>

            <div class="dash-panel dash-panel--donut">
                <div class="dash-panel__head">
                    <span class="dash-panel__title"><i class="icon devices"></i> Vehicle Status Overview</span>
                </div>
                <div class="dash-donut-wrap">
                    <div class="dash-donut" style="background: conic-gradient({{ $donutGradient }})">
                        <div class="dash-donut__center">
                            <b>{{ $total }}</b><span>Total</span>
                        </div>
                    </div>
                    <ul class="dash-donut__legend">
                        @foreach ($donut as $d)
                            <li>
                                <i style="background: {{ $d['color'] }}"></i>
                                <span>{{ $d['label'] }}</span>
                                <b>{{ $d['count'] }}</b>
                                <em>{{ $d['pct'] }}%</em>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Bottom row: devices + alerts + activity --}}
        <div class="dash-bottom-row">
            <div class="dash-panel dash-panel--devices">
                <div class="dash-panel__head">
                    <span class="dash-panel__title"><i class="icon devices"></i> Devices ({{ $total }})</span>
                </div>
                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <thead>
                        <tr>
                            <th>{{ trans('front.device') }}</th>
                            <th>{{ trans('front.status') }}</th>
                            <th>{{ trans('front.speed') }}</th>
                            <th>{{ trans('front.last_seen') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($devices as $device)
                            <tr>
                                <td class="dash-table__name">
                                    <span class="dash-table__dot" style="background: {{ $device->getStatusColor() }}"></span>
                                    {{ $device->name }}
                                </td>
                                <td><span class="dash-status-pill" style="--c: {{ $device->getStatusColor() }}">{{ ucfirst($statusOf[$device->id] ?? 'offline') }}</span></td>
                                <td>{{ Formatter::speed()->human($device->getSpeed()) }}</td>
                                <td>{{ $device->time }}</td>
                                <td><a class="dash-table__open" href="{!! route('objects.index') !!}" target="_blank">Open</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dash-panel dash-panel--alerts">
                <div class="dash-panel__head">
                    <span class="dash-panel__title"><i class="icon alerts"></i> Alerts &amp; Events
                        @if($events->count())<b class="dash-badge">{{ $events->count() }}</b>@endif
                    </span>
                </div>
                <ul class="dash-list">
                    @forelse ($events as $event)
                        <li>
                            <span class="dash-list__dot" style="background: {{ $event->alert_id ? '#f97316' : '#9aa5b1' }}"></span>
                            <div class="dash-list__body">
                                <b>{{ $event->device_name ?: '—' }}</b>
                                <span>{{ $event->message }}</span>
                            </div>
                            <time>{{ $event->time }}</time>
                        </li>
                    @empty
                        <li class="dash-list__empty">{{ trans('front.nothing_found') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="dash-panel dash-panel--activity">
                <div class="dash-panel__head">
                    <span class="dash-panel__title"><i class="icon time"></i> Recent Activity</span>
                </div>
                <ul class="dash-list">
                    @forelse ($activity as $device)
                        <li>
                            <span class="dash-list__dot" style="background: {{ $device->getStatusColor() }}"></span>
                            <div class="dash-list__body">
                                <b>{{ $device->name }}</b>
                                <span>{{ ucfirst($statusOf[$device->id] ?? 'offline') }}</span>
                            </div>
                            <time>{{ \Carbon\Carbon::parse($device->traccar->time)->diffForHumans() }}</time>
                        </li>
                    @empty
                        <li class="dash-list__empty">{{ trans('front.nothing_found') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    @include('Frontend.Layouts.partials.app')

    <script type="text/javascript">
        $(document).ready(function () {
            var markers = {!! json_encode($markers) !!};
            if (!markers.length || typeof L === 'undefined')
                return;

            var statusColor = { moving: '#388E3C', idle: '#0288D1', offline: '#E64A19' };
            var layers = {};
            var latlngs = [];

            var map = L.map('dashMap', { zoomControl: false }).setView([24.0, 67.0], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            markers.forEach(function (m) {
                var ll = [m.lat, m.lng];
                latlngs.push(ll);

                var mk = L.marker(ll, {
                    icon: L.divIcon({
                        className: 'dash-marker',
                        html: '<span style="background:' + (statusColor[m.status] || '#E64A19') + '"></span>',
                        iconSize: [18, 18],
                        iconAnchor: [9, 9]
                    })
                }).bindPopup('<b>' + m.name + '</b><br>' + m.status);

                (layers[m.status] = layers[m.status] || L.layerGroup()).addLayer(mk);
                mk.addTo(map);
            });

            if (latlngs.length)
                map.fitBounds(latlngs, { padding: [40, 40] });

            $('#dashMapFilters button').on('click', function () {
                var f = $(this).data('filter');
                $(this).addClass('active').siblings().removeClass('active');

                Object.keys(layers).forEach(function (s) {
                    var show = f === 'all' || s === f;
                    layers[s].eachLayer(function (mk) {
                        if (show && !map.hasLayer(mk))
                            map.addLayer(mk);
                        if (!show && map.hasLayer(mk))
                            map.removeLayer(mk);
                    });
                });
            });
        });
    </script>
@stop
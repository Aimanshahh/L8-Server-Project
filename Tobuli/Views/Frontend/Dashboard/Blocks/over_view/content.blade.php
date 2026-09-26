@php($totalDevices = (int) $total)
<div class="overview-summary">
    <div class="overview-summary-heading">
        <strong>{{ $totalDevices }}</strong>
        <span>{{ trans('front.devices') }}</span>
    </div>
    <div class="overview-status-bar" aria-label="{{ trans('front.devices') }}">
        @foreach($statuses as $status)
            @php($percentage = $totalDevices ? round(($status['data'] / $totalDevices) * 100, 1) : 0)
            <span class="overview-status-segment" style="width: {{ $percentage }}%; background-color: {{ $status['color'] }}"></span>
        @endforeach
    </div>
    <div class="overview-legend">
        @foreach($statuses as $status)
            <span class="overview-legend-item">
                <i style="background-color: {{ $status['color'] }}"></i>
                <span>{{ $status['label'] }}</span>
                <b>{{ $status['data'] }}</b>
            </span>
        @endforeach
    </div>
</div>

<div class="row row-status">
    @foreach($statuses as $status)
        <div class="col-xs-6 col-sm-4 col-md-2 statuss">
            <a class="stat-box" style="--status-color: {{ $status['color'] }}" href="{{ $status['url'] }}" target="_blank">
            <span class="status-marker"></span>
            <div class="count">{{ $status['data'] }}</div>
            <div class="title">{{ $status['label'] }}</div>
            <span class="stat-btn btn">{{ trans('global.view_details') }}</span>

            </a>
        </div>
    @endforeach
</div>

<script type='text/javascript'>
    if ($('#dashboard').is(':visible'))
        setTimeout(function () {
            app.dashboard.loadBlockContent('over_view', true);
        }, 10000);
</script>

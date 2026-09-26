@extends('Frontend.Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('Frontend.Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    @foreach($report->metas() as $meta)
                        <th>{{ $meta['title'] }}</th>
                    @endforeach
                    <th>{{ trans('front.route_start') }}</th>
                    <th>{{ trans('front.route_end') }}</th>
                    <th>{{ trans('front.route_length') }}(Km)</th>
                    <th>{{ trans('front.move_duration') }}</th>
                    <th>{{ trans('front.stop_duration') }}</th>
                    <th>{{ trans('front.stop_count') }}</th>
                    <th>{{ trans('front.top_speed') }}</th>
                    <th>{{ trans('front.average_speed') }}</th>
                    <th>{{ trans('front.overspeed_count') }}</th>
                    <th>{{ trans('front.odometer') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($report->getItems() as $item)
                    <tr>
                    @foreach($item['meta'] as $key => $meta)
                        <td>{{ $meta['value'] }}</td>
                    @endforeach
                    @if (isset($item['error']))
                            <td colspan="10">{{ $item['error'] }}</td>
                    @else
                        <td>{{ $item['totals']['start_at'] }}</td>
                        <td>{{ $item['totals']['end_at'] }}</td>
                        @php
                        $kmd = $item['totals']['distance'];
                        if(strpos($kmd, 'Km'))
                        {
                        $kmd = str_replace('Km', '', $kmd);         
                        }
                        @endphp
                        <td>{{ $kmd }}</td>
                        <td>{{ $item['totals']['drive_duration'] }}</td>
                        <td>{{ $item['totals']['stop_duration'] }}</td>
                        <td>{{ $item['totals']['stop_count'] }}</td>
                        @php
                        $spda = $item['totals']['speed_avg'];
                        if(strpos($spda, 'kph'))
                        {
                            $spda = str_replace('kph', '', $spda);         
                        }
                        $spdm = $item['totals']['speed_max'];
                        if(strpos($spdm, 'kph'))
                        {
                            $spdm = str_replace('kph', '', $spdm);         
                        }
                       @endphp 
                        <td>{{ $spdm }}</td>
                        <td>{{ $spda }}</td>
                       
                        <td>{{ $item['totals']['overspeed_count'] }}</td>
                        @php
                        $kmo = $item['totals']['odometer'];
                        if(strpos($kmo, 'km'))
                        {
                        $kmo = str_replace('km', '', $kmo);         
                        }
                        @endphp
                        <td>{{ $kmo }}</td>
                    @endif
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        @foreach($report->metas() as $meta)
                            <td></td>
                        @endforeach
                        <td></td>
                        <td></td>
                        @php
                                        $kmdt = $report->globalTotals('distance') ;
                                        if(strpos($kmdt, 'Km'))
                                        {
                                        $kmdt = str_replace('Km', '', $kmdt);         
                                        }
                                    @endphp
                        <td>{{ $kmdt }}</td>
                        <td>{{ $report->globalTotals('drive_duration') }}</td>
                        <td>{{ $report->globalTotals('stop_duration') }}</td>
                        <td>{{ $report->globalTotals('stop_count') }}</td>
                        <td></td>
                        <td></td>
                        <td>{{ $report->globalTotals('overspeed_count') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@stop
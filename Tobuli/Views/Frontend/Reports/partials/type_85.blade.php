@extends('front::Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('front::Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>{{ trans('global.date') }}</th>

                    @foreach($report->metas() as $meta)
                        <th>{{ $meta['title'] }}</th>
                    @endforeach

                    <th>{{ trans('front.from') }} (m3)</th>
                    <th>{{ trans('front.to') }} (m3)</th>
                    <th>{{ trans('front.difference') }} (m3)</th>
                    <th>{{ trans('front.from') }} (h)</th>
                    <th>{{ trans('front.to') }} (h)</th>
                    <th>{{ trans('front.difference') }} (h)</th>
                    <th>{{ trans('front.flow_rate') }} m3/h</th>
                    <th>{{ trans('front.flow_rate') }} l/s</th>
                    <th>{{ trans('front.location') }}</th>
                </tr>
                </thead>

                <tbody>
                @foreach($report->intervals as $date => $ignore)
                    @foreach($report->getItems() as $item)
                        @foreach($item['table'] as $rowDate => $row)
                            @if($rowDate !== $date)
                                @continue
                            @endif

                            <tr>
                                <td>{{ $date }}</td>

                                @foreach($item['meta'] as $key => $meta)
                                    <td>{{ $meta['value'] }}</td>
                                @endforeach

                                <td>{{ $row['net_amount_min'] }}</td>
                                <td>{{ $row['net_amount_max'] }}</td>
                                <td>{{ $row['net_amount_diff'] }}</td>
                                <td>{{ $row['engine_hours_from'] }}</td>
                                <td>{{ $row['engine_hours_to'] }}</td>
                                <td>{{ $row['engine_hours_diff'] }}</td>
                                <td>{{ $row['rate_m3_h'] }}</td>
                                <td>{{ $row['rate_l_s'] }}</td>
                                <td>{!! $row['location'] !!}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach
                </tbody>

                @php $totals = $report->globalTotals() @endphp

                <tfoot>
                <tr>
                    <th>{{ trans('front.total') }}</th>
                    <th colspan="{{ count($report->metas()) }}"></th>
                    <th></th>
                    <th></th>
                    <th>{{ $totals['net_amount_diff'] }}</th>
                    <th></th>
                    <th></th>
                    <th>{{ $totals['engine_hours_diff'] }}</th>
                    <th>{{ $totals['rate_m3_h'] }}</th>
                    <th>{{ $totals['rate_l_s'] }}</th>
                    <th></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@stop
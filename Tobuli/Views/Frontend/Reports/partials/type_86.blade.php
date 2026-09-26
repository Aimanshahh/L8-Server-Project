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
                       
                        <th>{{ trans('global.loading_zone') }} </th>
                        <th>{{ trans('global.loading_position') }} </th>
                        <th>{{ trans('global.loading_time') }} </th>
                        <th>{{ trans('global.unloading_zone') }} </th>
                        <th>{{ trans('global.unloading_position') }} </th>
                        <th>{{ trans('global.travel_time') }} </th>
                        <th>{{ trans('global.lead_distance') }} </th>
                        <th>{{ trans('global.cycle_time') }} </th>
                        <th>{{ trans('global.cycle_distance') }} </th>
                       
                </tr>
                </thead>
                <tbody>
                @foreach ($report->getItems() as $item)
                    @if (isset($item['error']))
                        <tr>
                            @foreach($item['meta'] as $key => $meta)
                                <td>{{ $meta['value'] }}</td>
                            @endforeach
                            <td colspan="4">{{ $item['error'] }}</td>
                           
                            
                        </tr>
                    @else
                    <tr>
                        @foreach ($item['table']['rows'] as $row)
                        
                                @foreach($item['meta'] as $key => $meta)
                                    <td>{{ $meta['value'] }}</td>
                                @endforeach
                             
                                @if ($row['message'] == "Loading")
                                <td>{{ $row['message'] }}</td>
                                <td>{!! $row['location'] !!}</td>
                                <td>{{ $row['time'] }}</td>
                                <td>{{ $row['driver'] }}</td>
                                
                               
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                
                                
                                       
                                    @elseif ($row['message'] == "Unloading")
                                    <td>{{ $row['message'] }}</td>
                                <td>{!! $row['location'] !!}</td>
                                <td>{{ $row['time'] }}</td>
                                <td>{{ $row['driver'] }}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                

                                @endif
                            
                                
                                
                           
                        @endforeach
                        </tr>
                       
                        <tr>
                        @foreach ($item['table1']['rows1'] as $row)
                       
                            @foreach($item['meta'] as $key => $meta)
                            <td>{{ $meta['value'] }}</td>
                            @endforeach
                            <td>{{ $row['group_geofence'] }}</td>
                            <td>{{ $row['start_date_at'] }}</td>
                            <td>{{ $row['start_time_at'] }}</td>
                            <td>{{ $row['end_date_at'] }}</td>
                            <td>{{ $row['end_time_at'] }}</td>
                            <td>{{ $row['stop_duration'] }}</td>
                            <td>{{ $row['drive_distance'] }}</td>
                            <td></td>
                            <td></td>
                              
                            
                        
                        @endforeach
                        </tr>
                        @endif
                @endforeach
                </tbody>
                <tfoot>
                @foreach($report->globalTotals() as $title => $value)
                    <tr>
                        @foreach($report->metas('device') as $meta)
                            <th></th>
                        @endforeach
                        <th></th>
                        <th></th>
                        <th>{{ $title }}</th>
                        <th>{{ $value }}</th>
                    </tr>
                @endforeach
                </tfoot>
            </table>
            
           
        </div>
    </div>
@stop
@extends('Frontend.Reports.partials.layout')

@section('content')
@include('Frontend.Reports.partials.item_title')
   
        <div class="panel panel-default">
        
       

                    <div class="panel-body no-padding">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                          
                                @foreach($report->metas() as $meta)
                                    <th>{{ $meta['title'] }}</th>
                                @endforeach
                                <th>{{ trans('validation.attributes.status') }}</th>
                                <th>{{ trans('front.start') }}</th>
                                <th>{{ trans('front.end') }}</th>
                                <th>{{ trans('front.duration') }}</th>
                                <th>{{ trans('front.engine_idle') }}</th>
                                <th>{{ trans('front.driver') }}</th>
                                <th>{{ trans('front.altitude') }}(m)</th>

                                <th>{{ trans('front.stop_position') }}</th>
                                <th>{{ trans('front.route_length') }}(Km)</th>
                                <th>{{ trans('front.top_speed') }}(kph)</th>
                                <th>{{ trans('front.average_speed') }}(kph)</th>
                                <th>{{ trans('front.fuel_consumption') }}</th>
                                @if ($report->zones_instead)
                                    <th>{{ trans('front.geofences') }}</th>
                                @endif
                            </tr>
                          
                            </thead>

                            <tbody>
            @foreach ($report->getItems() as $item)
                        @if (isset($item['error']))
                        @include('Frontend.Reports.partials.item_empty')
                    @else
                    @if ( ! empty($item['table']['rows']))
                            @php
                            $device = ""; $device1 = "";
                            @endphp
                            @foreach ($item['table']['rows'] as $row)
                            @php 
                            $date_1 = $row['duration'];
                                   
                                  

                                    sscanf($date_1, "%d:%d:%d", $hours, $minutes, $seconds);

                                    $time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;  
                                           
                        @endphp
                        @if ($time_seconds > $report->stop_seconds)
                                <tr>
                                   
                                    @foreach($item['meta'] as $meta)
                                    <td>{{ $meta['value'] }}</td>
                                    @endforeach
                               
                        
                                    <td>{{ $row['status'] }}</td>
                                    <td>{{ $row['start_at'] }}</td>
                                    <td>{{ $row['end_at'] }}</td>
                                    <td>{{ $row['duration'] }}</td>
                                    <td>{{ $row['engine_idle'] }}</td>
                                    <td>{{ $row['drivers'] }}</td>
                                    <td>{{ $row['altitude'] }} </td>
                                    @if ($row['group_key'] != 'drive')
                                    <td>{!! $row['location'] !!}</td>
                                    @else
                                    <td></td>
                                        @endif

                                    @if ($row['group_key'] == 'drive')
                                    
                                                @php
                                        $kmd = $row['distance'];
                                        if(strpos($kmd, 'Km'))
                                        {
                                            $kmd = str_replace('Km', '', $kmd);         
                                        }
                                        $speed_m = $row['speed_max'];
                                        if(strpos($speed_m, 'kph'))
                                        {
                                            $speed_m = str_replace('kph', '', $speed_m);         
                                        }
                                        $speed_a = $row['speed_avg'];
                                        if(strpos($speed_a, 'kph'))
                                        {
                                            $speed_a = str_replace('kph', '', $speed_a);         
                                        }
                                    @endphp
                                        <td>{{ $kmd  }}</td>
                                        <td>{{ $speed_m }}</td>
                                        <td>{{ $speed_a}}</td>
                                    @else
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    @endif
                                    <td>{{ $row['fuel_consumption'] }}</td>
                                    @if ($report->zones_instead)
                                        <td>{{ array_get($row, 'geofences_in') }}</td>
                                    @endif
                                </tr>
                                @endif
                            @endforeach

                            @endif

@endif
       @endforeach
                                  
                            @foreach($item['meta'] as $meta)
                                @php
                                    $device = $meta['key'];
                                 @endphp 
                               
                               @endforeach
                              
                            </tbody>
                        </table>
                    </div>
              
        </div>
    
@stop
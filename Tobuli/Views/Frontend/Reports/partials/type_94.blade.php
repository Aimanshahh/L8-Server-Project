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
                                <th>{{ trans('front.start') }}</th>
                                <th>{{ trans('front.end') }}</th>
                                <th>{{ trans('front.duration') }}(Km)</th>
                                <th>{{ trans('front.engine_idle') }}</th>
                                <th>{{ trans('front.driver') }}</th>
                                <th>{{ trans('front.stop_position') }}</th>
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
                        @if ( ! empty($item['table']))
                        
                            @foreach ($item['table']['rows'] as $row)
                            @php 
                            $geo = array_get($row, 'geofences_in');
                            $date_1 = $row['duration'];
                                   
                                  

                                    sscanf($date_1, "%d:%d:%d", $hours, $minutes, $seconds);

                                    $time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;  
                                           
                             @endphp
                @if ($geo != "")     
                        @if ($time_seconds > $report->stop_seconds)
                                <tr>
                            
                                    @foreach($item['meta'] as $meta)
                               
                                        <td>{{ $meta['value'] }}</td>
                                      
                                 @endforeach
                              
                                    <td>{{ $row['start_at'] }}</td>
                                    <td>{{ $row['end_at'] }}</td>
                                    <td>{{ $row['duration'] }}</td>
                                    <td>{{ $row['engine_idle'] }}</td>
                                    <td>{{ $row['drivers'] }}</td>
                                    <td>{!! $row['location'] !!}</td>

                                    @if ($report->zones_instead)
                                        <td>{{ array_get($row, 'geofences_in') }}</td>
                                    @endif
                                </tr>
                            @endif
                            @endif
                            @endforeach
                            @foreach($item['meta'] as $meta)
                                @php
                                    $device = $meta['key'];
                                 @endphp 
                               
                               @endforeach

                               @endif

             
                            @endif
                    @endforeach
                            </tbody>
                        </table>
                    </div>
             
        </div>
    
@stop
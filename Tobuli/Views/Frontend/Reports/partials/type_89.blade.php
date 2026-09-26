@extends('Frontend.Reports.partials.layout')

@section('content')
   
        <div class="panel panel-default">
          

         
                    <div class="panel-body no-padding">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                @foreach($report->metas() as $meta)
                                    <th>{{ $meta['title'] }}</th>
                                @endforeach
                                <th>{{ trans('front.zone_in') }}</th>
                                <th>{{ trans('front.zone_out') }}</th>
                                <th>{{ trans('front.stop_duration') }}</th>
                                <th>{{ trans('global.distance') }}(Km)</th>
                                <th>{{ trans('validation.attributes.geofence_name') }}</th>
                                <th>{{ trans('front.position') }}</th>
                                
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
                    @if ( ! empty($item['table']['rows']))
                            @foreach ($item['table']['rows'] as $row)
                            
                            @php 
                            $date_1 = $row['stop_duration'];
                                   
                                  

                                    sscanf($date_1, "%d:%d:%d", $hours, $minutes, $seconds);

                                    $time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;  
                                           
                                        @endphp
                            @if ($time_seconds > $report->stop_seconds)
                                <tr>
                                @foreach($item['meta'] as $key => $meta)
                                    <td>{{ $meta['value'] }}</td>
                                @endforeach
                                    <td>{{ $row['start_at'] }}</td>
                                    <td>{{ $row['end_at'] }}</td>
                                    <td>{{ $row['stop_duration'] }}</td>
                                    <td>{{ $row['distance'] }}</td>
                                    <td>{{ $row['group_geofence'] }}</td>
                                    <td>{!! $row['location'] !!}</td>
                                   
                                </tr>
                                @endif
                            @endforeach
                     @endif

                     @endif
                            @endforeach
                            </tbody>
                    
                        </table>
                    </div>
               

               
           
        </div>
   
@stop
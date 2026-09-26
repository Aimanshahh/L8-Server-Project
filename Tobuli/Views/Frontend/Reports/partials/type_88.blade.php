@extends('Frontend.Reports.partials.layout')

@section('content')
   
        <div class="panel panel-default">
          

        @include('Frontend.Reports.partials.item_heading_new')
                    <div class="panel-body no-padding">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                          
                                @foreach($report->metas() as $meta)
                                <th>Sno</th>
                                <th>{{ $meta['title'] }}</th>
                                 
                                @endforeach
                                <th>{{ trans('front.zone_in') }}</th>
                                <th>{{ trans('front.zone_out') }}</th>
                                <th>{{ trans('front.duration') }}</th>
                                <th>{{ trans('global.distance') }}</th>
                                <th>{{ trans('validation.attributes.geofence_name') }}</th>
                                <th>{{ trans('front.position') }}</th>
                                <th>{{ trans('front.average') }}</th>
                                
                            </tr>
                            </thead>

                            <tbody>
                            @php 
                                   
                                     $count1 = "1";
                                     $count = "1";
                                     $group1 = "";
                                     $group = "";
                                     $geofence = "";
                                @endphp  
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
                   
                    @foreach($item['meta'] as $key => $meta)
                    <tr>      
                    @php 
                                     $group1 = $meta['group'];
                                   
                                @endphp   
                             
                                @if ($group == $group1)
                               
                                @php 
                                     $count1 = $count1 + 1;
                                   
                                @endphp
                                <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  @else
                                  @php 
                                     $count1 = 1;
                                   
                                @endphp
                            
                                  <td style="color: blue;font-weight:700;">{{ $group1 }}</td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                               
                                  @endif
                                  </tr>
                                  @endforeach
                            @foreach ($item['table']['rows'] as $row)
                            <tr>
                                     @php 
                                        $date_1 = $row['duration'];
                                    
                                    

                                        sscanf($date_1, "%d:%d:%d", $hours, $minutes, $seconds);

                                        $time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;  
                                            
                                        @endphp
                            @if ($time_seconds > $report->stop_seconds)
                                
                               

                                @foreach($item['meta'] as $key => $meta)
                                @php 
                                     $group = $meta['group'];
                                   
                                @endphp
                              
                                <td>{{ $count1 }}</td>
                                 
                                     <td>{{ $meta['value'] }}</td>
                                
                               
                                    <td>{{ $row['start_at'] }}</td>
                                    <td>{{ $row['end_at'] }}</td>
                                    <td>{{ $row['duration'] }}</td>
                                    <td>{{ $row['distance'] }}</td>
                                    <td>{{ $row['group_geofence'] }}</td>
                                    <td>{!! $row['location'] !!}</td>
                                    <td></td>
                                    @endforeach
                                @endif

                         </tr>
                            @endforeach
                          
                     @endif

                     @endif
                            @endforeach
                            </tbody>
                    
                        </table>
                    </div>
               

               
           
        </div>
   
@stop
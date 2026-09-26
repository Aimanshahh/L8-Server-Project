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
                                <th>{{ trans('front.position_a') }}</th>
                                <th>{{ trans('front.leave') }}</th>
                                <th>{{ trans('front.duration') }}</th>
                                <th>{{ trans('front.route_length') }}(Km)</th>
                                <th>{{ trans('front.position_b') }}</th>
                                <th>{{ trans('front.end') }}</th>
                                <th>{{ trans('front.time_at_location') }}</th>
                                <th>{{ trans('front.departure_time') }}</th>
                                <th>{{ trans('front.average_speed') }}(kph)</th>
                                <th>{{ trans('front.top_speed') }}(kph)</th>
                                <th>{{ trans('front.total_route_length') }}</th>
                                <th>{{ trans('front.total_move_duration') }}</th>
                            </tr>
                            </thead>

                            <tbody>
                        @foreach ($report->getItems() as $item)
                        @if (isset($item['error']))
                            @include('Frontend.Reports.partials.item_empty')
                        @else
                        @if ( ! empty($item['table']['rows']))
                        @php
                            $device = ""; $device1 = ""; $count = "0";
                            @endphp
                            @foreach(array_chunk($item['table']['rows'], 2) as $chunk)
                                <tr>
                                @if ($device == $device1)
                                    @foreach($item['meta'] as $meta)
                               
                                       
                                        @php
                                            $device1 = $meta['key'];
                                           
                                        @endphp 
                                 @endforeach
                                 @php
                                 $count++;
                                 @endphp 
                                @else
                                    @foreach($item['meta'] as $meta)
                                      
                                    @endforeach
                                @endif

                                @php
                                $kmd = $chunk[0]['distance'];
                                    if(strpos($kmd, 'Km'))
                                    {
                                        $kmd = str_replace('Km', '', $kmd);         
                                    }
                                  $speed_m = $chunk[0]['speed_max'];
                                  if(strpos($speed_m, 'kph'))
                                  {
                                      $speed_m = str_replace('kph', '', $speed_m);         
                                  }
                                  $speed_a = $chunk[0]['speed_avg'];
                                  if(strpos($speed_a, 'kph'))
                                  {
                                      $speed_a = str_replace('kph', '', $speed_a);         
                                  }
                              @endphp
                              @foreach($item['meta'] as $meta)
                               
                                        <td>{{ $meta['value'] }}</td>
                                       
                                 @endforeach
                                    <td>{!! $chunk[0]['location_start'] !!}</td>
                                    <td>{{ $chunk[0]['start_at'] }}</td>
                                    <td>{{ $chunk[0]['duration'] }}</td>
                                    <td>{{ $kmd }}</td>
                                    <td>{!! $chunk[0]['location_end'] !!}</td>
                                    <td>{{ $chunk[0]['end_at'] }}</td>
                                    <td>{{ array_get($chunk, '1.duration') }}</td>
                                    <td>{{ array_get($chunk, '1.end_at') }}</td>
                                    <td>{{ $speed_a }}</td>
                                    <td>{{ $speed_m }}</td>
                                    @if ($count == "1")
                                    @include('Frontend.Reports.partials.item_total_travel_sheet')
                                    @php
                                 $count = 0;
                                 @endphp 
                                    @else
                                    <td></td>
                                    <td></td>
                                    @endif
                                </tr>
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
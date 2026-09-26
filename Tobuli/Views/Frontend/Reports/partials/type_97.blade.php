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
                                <th>{{ trans('front.duration') }}</th>
                                <th>{{ trans('front.top_speed') }}(kph)</th>
                                <th>{{ trans('front.average_speed') }}(kph)</th>
                                <th>{{ trans('front.position') }}</th>
                                <th>{{ trans('front.underspeed_count') }}</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach ($report->getItems() as $item)
                            @if (isset($item['error']))
                                @include('Frontend.Reports.partials.item_empty')
                             @else

                        @if ( ! empty($item['table']))
                        @php
                            $device = ""; $device1 = "";$count = "0";
                            @endphp
                            @foreach ($item['table']['rows'] as $row)
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
                                @foreach($item['meta'] as $meta)
                               
                                <td>{{ $meta['value'] }}</td>
                             
                                @endforeach
                                    <td>{{ $row['start_at'] }}</td>
                                    <td>{{ $row['end_at'] }}</td>
                                    <td>{{ $row['duration'] }}</td>
                                    <td>{{ $speed_m }}</td>
                                    <td>{{ $speed_a }}</td>

                                    <td>{!! $row['location'] !!}</td>
                                    @if ($count == "1")
                                        @include('Frontend.Reports.partials.item_total_travel_sheet')
                                        @php
                                            $count = 0;
                                        @endphp 
                                        @else
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
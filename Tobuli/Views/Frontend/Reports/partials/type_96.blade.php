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
                                <th>{{ trans('validation.attributes.date') }}</th>
                                <th>{{ trans('front.duration') }}</th>
                                <th>{{ trans('front.position_a') }}</th>
                                <th>{{ trans('front.position_b') }}</th>
                                <th>{{ trans('front.route_length') }}(Km)</th>
                                <th>{{ trans('front.driver') }}</th>
                                @if ( ! empty($item['table']['rows'][0]['fuel_consumption_list']))
                                    @foreach($item['table']['rows'][0]['fuel_consumption_list'] as $row)
                                    <th>{{ $row['title'] }}</th>
                                    @endforeach
                                @endif
                                @if ( ! empty($item['table']['rows'][0]['fuel_price_list']))
                                    @foreach($item['table']['rows'][0]['fuel_price_list'] as $row)
                                        <th>{{ $row['title'] }}</th>
                                    @endforeach
                                @endif
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
                                    $kmd = $row['distance'];
                                    if(strpos($kmd, 'Km'))
                                    {
                                        $kmd = str_replace('Km', '', $kmd);         
                                    }
                                
                                @endphp
                                @foreach($item['meta'] as $meta)
                               
                               <td>{{ $meta['value'] }}</td>
                            
                               @endforeach
                                    <td>{{ $row['start_at'] }}</td>
                                    <td>{{ $row['duration'] }}</td>
                                    <td>{!! $row['location_start'] !!}</td>
                                    <td>{!! $row['location_end'] !!}</td>
                                    <td>{{ $kmd }}</td>
                                    <td>{{ $row['drivers'] }}</td>

                                    @if ( ! empty($row['fuel_consumption_list']))
                                        @foreach($row['fuel_consumption_list'] as $_row)
                                            <td>{{ $_row['value'] }}</td>
                                        @endforeach
                                    @endif
                                    @if ( ! empty($row['fuel_price_list']))
                                        @foreach($row['fuel_price_list'] as $_row)
                                            <td>{{ $_row['value'] }}</td>
                                        @endforeach
                                    @endif
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
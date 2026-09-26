@extends('Frontend.Reports.partials.layout')

@section('content')
   
        <div class="panel panel-default">
        @include('Frontend.Reports.partials.item_title')  

         
                    <div class="panel-body no-padding">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                            @foreach($report->metas() as $meta)
                                    <th>{{ $meta['title'] }}</th>
                                @endforeach
                                <th>{{ trans('front.date') }}</th>
                                <th>{{ trans('front.ignition_on') }}</th>
                                <th>{{ trans('front.event_time') }}</th>

                                <th>{{ trans('front.ignition_off') }}</th>
                                <th>{{ trans('front.event_time') }}</th>
                                <th>{{ trans('front.speed') }}(kph)</th>
                                <th>{{ trans('front.trip_distance') }}(Km)</th>
                                <th>{{ trans('front.engine_work') }}</th>
                                <th>{{ trans('front.stopped_for') }}</th>
                                <th>{{ trans('front.driver') }}</th>
                             
                                @if ($report->zones_instead)
                                    <th>{{ trans('front.geofences') }}</th>
                                @else
                                    <th>{{ trans('front.location') }}</th>
                                @endif
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
                    @if ( ! empty($item['table']))
                    @php
                            $device = ""; $device1 = "";
                            @endphp
                            @foreach ($item['table']['rows'] as $row)
                            <tr>
                                   @if ($device == $device1 || $row['group_key'] == 'date')
                                    @foreach($item['meta'] as $meta)
                               
                                        <td>{{ $meta['value'] }}</td>
                                        
                                        @php
                                            $device1 = $meta['key'];
                                        @endphp 
                                 @endforeach
                                 <td><strong>{{ $row['date'] }}</strong></td>
                                @else
                                    @foreach($item['meta'] as $meta)
                                        <td></td>
                                       
                                    @endforeach
                                    <td></td>
                                @endif
                                @if ($row['group_key'] == 'engine_on') 
                                @php
                                    $kmd = $row['distance'];
                                    if(strpos($kmd, 'Km'))
                                    {
                                        $kmd = str_replace('Km', '', $kmd);         
                                    }
                                
                                    $speed_a = $row['speed_avg'];
                                    if(strpos($speed_a, 'kph'))
                                    {
                                        $speed_a = str_replace('kph', '', $speed_a);         
                                    }
                                @endphp 
                                 
                                        <td>{{ trans('front.on') }}</td>
                                        <td>{{ $row['time'] }}</td>
                                        <td></td>
                                        <td></td>
                                        <td>{{ $speed_a }}</td>
                                        <td>{{ $kmd }}</td>
                                        <td>{{ $row['duration'] }}</td>
                                        <td></td>
                                        <td>{{ $row['drivers'] }}</td>
                                       
                                        @if ($report->zones_instead)
                                            <td>{{ array_get($row, 'geofences_in') }}</td>
                                            @else
                                            <td>{!! $row['location'] !!}</td>
                                        @endif
                                    
                                @elseif ($row['group_key'] == 'engine_off')
                                  
                                        <td></td>
                                        <td></td>
                                        <td>{{ trans('front.off') }}</td>
                                        <td>{{ $row['time'] }}</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>{{ $row['duration'] }}</td>
                                        <td></td>
                                       
                                        @if ($report->zones_instead)
                                            <td>{{ array_get($row, 'geofences_in') }}</td>
                                            @else
                                            <td>{!! $row['location'] !!}</td>
                                        @endif
                                @endif
                                </tr>
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
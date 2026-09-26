@extends('Frontend.Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
   @php
    function sum_time(Array $durations, $c) {
        $total_time = 0;
        for($durations as $duration) {  
            
              $total_time += $duration;
            }  
         
    $c1 = sprintf('%01.1f', ($c));
    return sprintf('%01.1f', ($total_time/$c1));
}
@endphp
        @include('Frontend.Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                
                    @foreach($report->metas() as $meta)
                    <th>Sno</th>
                    <th>{{ $meta['title'] }}</th>
                   
                    @endforeach
                    <th>{{ trans('front.operator_name') }}</th>
                    <th>{{ trans('front.engine_hours') }}</th>
                    <th>{{ trans('front.idle_duration') }}</th>
                    <th>{{ trans('front.idle') }} %</th>
                    <th>{{ trans('front.km') }}</th>
                    <th>{{ trans('front.trips') }}</th>
                    <th>{{ trans('front.speed') }}</th>
                    <th>{{ trans('front.km/hr') }}</th>
                    <th>{{ trans('front.trips/hr') }}</th>
                  
                </tr>
              
                </thead>
                <tbody>
                        @php 
                        $c = "";
                        $ar=0;
                        $durations=[];
                        $durations1=[];
                        $durations2=[];
                        $durations4=[];
                        $durations6=[];
                        $durations7=[];
                        $avg_running = "";
                                    $count2 = "1";
                                     $count1 = "1";
                                     $count = "1";
                                     $group1 = "";
                                     $group = "";
                                     $group2 = "";
                                     $group4 = "";
                                     $geofence = "";
                                @endphp  
                @foreach ($report->getItems() as $item)
                  
                        @foreach($item['meta'] as $key => $meta)
                          
                                @php 
                                     $group1 = $meta['group'];
                                @endphp   
                             
                                @if ($group == $group1)
                               
                                @php 
                                     $count1 = $count1 + 1;
                                     
                                @endphp
                            
                                  @else

                                    @php 
                                        $count1 = 1;
                                    
                                    @endphp
                                    @if ($c !=0)
                                    <tr style="background:#eee;font-weight:700;">
                                    @if ($durations != 0)
                                            @php 
                                            $avg_running = sum_time($durations, $c);
                                            @endphp
                                        @else
                                            @php
                                            $avg_running = 0;
                                            @endphp
                                        @endif
                                        @if ($durations1 != 0)
                                            @php 
                                            $avg_idle_durations = sum_time($durations1, $c);
                                            @endphp
                                        @else
                                            @php
                                            $avg_idle_durations = 0;
                                            @endphp
                                        @endif
                                        @if ($durations6 != 0)
                                            @php 
                                            $avg_km_hr = sum_time($durations6, $c);
                                            @endphp
                                        @else
                                            @php
                                            $avg_km_hr = 0;
                                            @endphp
                                        @endif
                                        @if ($durations4 != 0 || $durations7 != 0)
                                            @php
                                            $avg_trip = sum_time($durations4, $c);
                                            $avg_trip_hr = sum_time($durations7, $c);
                                            @endphp
                                        @else
                                            @php
                                            $avg_trip = 0;
                                            $avg_trip_hr = 0;
                                            @endphp
                                        @endif
                                   
                                    <td>Avg</td>
                                    <td>{{ $c }}</td>
                                    <td></td>
                                    <td>{{ $avg_running }}</td>
                                    <td>{{ $avg_idle_durations }}</td>
                                    <td></td>
                                    <td></td>
                                    <td>{{ $avg_trip }}</td>
                                    <td></td>
                                    <td>{{ $avg_km_hr }}</td> 
                                    <td>{{ $avg_trip_hr }}</td>
                                    </tr>
                                @endif

                                <tr>
                                    
                                    
                                  <td></td>
                                  <td></td>
                                  <td style="color: blue;font-weight:700;">{{ $group1 }}</td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  <td></td>
                                  </tr>
                                  @php 
                                     $count = 0;
                                     $ar=0;
                                     $durations=[];$durations1=[];
                        $durations2=[];
                        $durations4=[];
                        $durations6=[];$durations7=[];
                                   @endphp
                              
                                @endif
                                 
                            @endforeach
                 
                <tr>
                    @foreach($item['meta'] as $key => $meta)
                    <td>{{ $count1 }}</td>
                        <td>{{ $meta['value'] }}</td>
                        <td>{{ $meta['driver'] }}</td>
                             @php 
                             $group = $meta['group'];
                            @endphp
                                        
                    @endforeach
                    @if (isset($item['error']))
                            <td colspan="11">{{ $item['error'] }}</td>
                            @php 
                                     $count = $count + 1;
                                   
                                @endphp
                    @else
                      
                    <td>
                       @php 
                        $running1 = $item['totals']['engine_hours'];
                        sscanf($running1, "%d:%d:%d", $hours, $minutes, $seconds);
                        $running_hours = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 3600 + $minutes * 60;  
                        $running_hours1 = sprintf('%02d', floor($running_hours/3600));
                        $running_hours2 = sprintf('%01.1f', round($running_hours/60%60)/60);
                        $running = sprintf('%01.1f', $running_hours1 + $running_hours2);
                        @endphp
                        
                       {{ $running }}
                    </td>
                           
                        <td>
                            @php
                                $idle1 = $item['totals']['engine_idle'];
                                sscanf($idle1, "%d:%d:%d", $hours, $minutes, $seconds);
                                $idle_duration = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 3600 + $minutes * 60;  
                                $idle_duration1 = sprintf('%02d', floor($idle_duration/3600));
                                $idle_duration2 = sprintf('%01.1f', round($idle_duration/60%60)/60);
                                $idle_durations = sprintf('%01.1f', $idle_duration1 + $idle_duration2);
                             @endphp
                                {{ $idle_durations }}
                        </td>
                        @if ($idle_durations == 0 || $running == 0)
                        <td>0 %</td>
                            @else
                        <td style="color: red;font-weight:700;"> 
                            @php  
                            $idle = sprintf('%01.1f', (($idle_durations/$running)*100));
                            @endphp
                            {{ round($idle) }} %
                        </td>
                        @endif
                        @php
                        $kmd = $item['totals']['distance'];
                        if(strpos($kmd, 'Km'))
                        {
                            $kmd = str_replace('Km', '', $kmd);         
                        }
                       @endphp
                       
                        <td>{{ $kmd }}</td>

                            @foreach ($report->columns as $key => $value)
                                @php
                                $trip = $item['table']['total'] ?? 0;
                               
                                @endphp
                            <td style="color: red;font-weight:700;">{{ $item['table']['total'] ?? 0 }}</td>
                            @endforeach
                    @php
                        $spd = $item['totals']['speed_avg'];
                        if(strpos($spd, 'kph'))
                        {
                            $spd = str_replace('kph', '', $spd);         
                        }
                       @endphp 
                      <td>{{ $spd }}</td>
                      
                    
                    @if ($running == 0 || $kmd == 0)
                   
                            <td> 0</td>
                    @else
                    <td style="color: red;font-weight:700;"> 
                        @php
                        $khr = sprintf('%01.1f', ($kmd)); 
                        $km_hr = sprintf('%01.1f', ($khr/$running)); 
                        @endphp
                          {{ $km_hr }}  
                    </td>
                    @endif
                    
                 @if ($running == 0 || $trip == 0)
                            <td> 0</td>
                            @php 
                            $trip_hr = 0;
                            @endphp
                            @else
                           
                                <td style="color: red;font-weight:700;"> 
                                    @php  
                                    $trip_hr = sprintf('%01.1f', ($trip/$running));
                                    @endphp
                                    {{ $trip_hr }}
                                </td>
                        @endif
                        @php
                            $c = $count1 - $count;
                            $durations[$ar] = $running;
                            $durations1[$ar] = $idle_durations;
                            $durations4[$ar] = $trip;

                            $durations6[$ar] = $km_hr;
                            $durations7[$ar] = $trip_hr;
                            $ar++;
                           
                        @endphp
             
                    

                      
                    @endif
                    </tr>
                @endforeach
                            
                </tbody>
                <tfoot>
              
                   
                        @if ($durations != 0)
                            @php 
                            $avg_running = sum_time($durations, $c);
                            @endphp
                        @else
                            @php
                            $avg_running = 0;
                            @endphp
                        @endif
                        @if ($durations1 != 0)
                            @php 
                            $avg_idle_durations = sum_time($durations1, $c);
                            @endphp
                        @else
                            @php
                            $avg_idle_durations = 0;
                            @endphp
                        @endif
                        @if ($durations6 != 0)
                            @php 
                            $avg_km_hr = sum_time($durations6, $c);
                            @endphp
                         @else
                            @php
                            $avg_km_hr = 0;
                            @endphp
                        @endif
                  
                        @if ($durations4 != 0)
                        @php
                        $avg_trip = sum_time($durations4, $c);
                        $avg_trip_hr = sum_time($durations7, $c);
                        @endphp
                        @else
                        @php
                        $avg_trip = 0;
                        $avg_trip_hr = 0;
                        @endphp
                        @endif
                  
                    <tr>
                    <td>Avg</td>
                    <td>{{ $count1 - $count }}</td>
                    <td></td>
                    <td>{{ $avg_running }}</td>
                    <td>{{ $avg_idle_durations }}</td>
                    <td></td>
                    <td></td>
                    <td>{{ $avg_trip }}</td>  
                    <td></td> 
                    <td>{{ $avg_km_hr }}</td> 
                    <td>{{ $avg_trip_hr }}</td>
                    </tr>
                   
                   
                    
                </tfoot>
            </table>
        </div>
    </div>
@stop
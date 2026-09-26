@extends('Frontend.Reports.partials.layout')

@section('content')
    <div class="panel panel-default">
        @include('Frontend.Reports.partials.item_heading')

        <div class="panel-body no-padding">
            <table class="table table-hover">
                <thead>
                <tr>
                    @foreach($report->metas('device') as $meta)
                        <th>{{ $meta['title'] }}</th>
                    @endforeach
                    <th>{{ trans('front.event') }}</th>
                    <th>{{ trans('front.count') }}</th>
                   
                </tr>
                </thead>
                <tbody>
                @foreach ($report->getItems() as $item)
               
                    @if (isset($item['error']))
                        <tr>
                            @foreach($item['meta'] as $key => $meta)
                        
                            <td>{{ $meta['value'] }}</td>
                           
                            @endforeach
                            <td colspan="2">{{ $item['error'] }}</td>
                        </tr>
                    @else
                    <tr>
                    @foreach($item['meta'] as $key => $meta)
                                <td>{{ $meta['value'] }}</td>
                     @endforeach
                        @php 
                          
                            $event ='';
                            $event2 = '';
                            $ar=0;
                            $br=0;
                           
                           @endphp
                        @foreach ($item['table']['rows'] as $row)
                    
                                    @if ($row['message'] == "Loading")  
                                    @php
                                                $event = $row['message'] ;
                                                $count1 = $ar++;
                                                @endphp   
                                     @elseif ($row['message'] == "Unloading")
                                    @php
                                                $event2 = $row['message'] ;
                                                $count1 = $br++;
                                                @endphp   
                                    @endif
                                    @if ($row['message'] != "Loading" && $row['message'] != "Unloading")  
                                    @php $event = $row['message'] ;
                                                $count3 = $ar++;
                                                @endphp  
                                    @endif
                              
                        @endforeach
                       
                       
                        <td>{{ $event }}</td>
                        <td>{{ $ar }}</td>
                        
                        <td></td>
                             <td>{{ $event2 }}</td>
                             <td>{{ $br }}</td>
                        
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
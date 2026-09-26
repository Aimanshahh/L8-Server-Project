
    @foreach ($list as $key)
        @if( ! empty($totals[$key]))
            @if(is_array($totals[$key]['value']))
                @foreach($totals[$key]['value'] as $sub)
              
                    <td style="color: red;font-weight:700;">{{ $sub['value']}}</td>
               
                @endforeach
            @else
                @if( $totals[$key]['value'] != '' )
               
                    <td style="color: red;font-weight:700;">{{ $totals[$key]['value'] }}</td>
                
                @endif
            @endif
        @endif
    @endforeach

<?php
$widgets = getActingUser()->getSettings('widgets');

if (empty($widgets)) {
    $widgets = settings('widgets');
}
?>

@if( ! empty($widgets['status']) && ! empty($widgets['list']) )
    <div id="widgets" style="display: none;">
        <a class="btn-collapse btn-collapse-widget" onclick="app.changeSetting('toggleWidgets');"><i></i></a>

        <div class="widgets-content">
        <div class="widget">
                        <div class="widget-heading">
                            <div class="widget-title">
                            <i class="icon device"></i>
           
           <span data-device="name"></span>
                            <span data-device="status"></span>
                            </div>
                            <a class="widget-close" href="javascript:" title="Close" onclick="$('#widgets').hide();"><i class="icon x"></i></a>
                        </div>

                    
                    </div>
            @foreach( $widgets['list'] as $widget)
                @if (!config("lists.widgets.$widget"))
                    @continue
                @endif

                @include('Frontend.Widgets.'.$widget)
            @endforeach
        </div>
    </div>
@endif
<script>
    
     $('.btn-collapse-widget').on('click', function() {
        var custo = $('#widgets');
         
       // $('.left-sidebar').toggleClass('opened');
       var toggle = $('#map-controls'); 
        //e.preventDefault();                
    //e.preventDefault();
    var menu = toggle.hasClass('collapsedd');
    var side = custo.hasClass('collapsed')
    //console.log(menu);
    if(side == true){
        var j_width = $(window).width(); 
        console.log(j_width);
        
    $('#map-controls').animate({right: 10+"px"});


    }
    if(menu == true){
        var j_width = $(window).width(); 
        console.log(j_width);
        //$("#sidebar").animate({left: '0px'});
    // $("#sidebar_hide").animate({left: '70px'});
    $('#map-controls').animate({right: 260+"px"});
   

    }
    });  

    </script>

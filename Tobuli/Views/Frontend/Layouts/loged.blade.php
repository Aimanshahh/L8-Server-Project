<!DOCTYPE html>
<html lang="{{ Language::iso() }}" data-theme="{{ current_theme() }}">
<head>
    @include('Frontend.Layouts.partials.head')
    @yield('styles')
</head>

<body class="page" style="overflow: hidden;">

@include('Frontend.Popups.index_banners_top')
@include('Frontend.Layouts.partials.loading')
@include('Frontend.Layouts.partials.header')
<main class="main page__main">
<div id="sidebar">

    <a class="btn-collapse" onclick="app.changeSetting('toggleSidebar');"><i></i></a>
   
    <div class="sidebar-content">
    @if (!empty( $blocks['over_view'] ))
    <div id="block_over_view">
        <div class="dashboard-content">
            <div class="row status-row">
   	            {!!  $blocks['over_view'] !!}
	        </div>
        </div>
    </div>
    @endif
        <div class="op-header">
        <ul class="nav nav-tabs nav-default">
            <li role="presentation" class="active">
                <a href="#objects_tab" type="button" data-toggle="tab">{!!trans('front.objects')!!}</a>
            </li>
            @if(Auth::user()->perm('events', 'view'))
                <li role="presentation">
                    <a href="#events_tab" type="button" data-toggle="tab">{!!trans('front.events')!!}</a>
                </li>
            @endif
            <li role="presentation">
                <a href="#history_tab" type="button" data-toggle="tab">{!!trans('front.history')!!}</a>
            </li>
            {{-- hidden, import for correct tab work (shown, hidden evenets) --}}
            <li role="presentation" class="hidden"><a href="#alerts_tab" data-toggle="tab"></a></li>
            <li role="presentation" class="hidden"><a href="#geofencing_tab" data-toggle="tab"></a></li>
            <li role="presentation" class="hidden"><a href="#geofencing_create" data-toggle="tab"></a></li>
            <li role="presentation" class="hidden"><a href="#geofencing_edit" data-toggle="tab"></a></li>
            <li role="presentation" class="hidden"><a href="#routes_tab" data-toggle="tab"></a></li>
            <li role="presentation" class="hidden"><a href="#routes_create" data-toggle="tab"></a></li>
            <li role="presentation" class="hidden"><a href="#routes_edit" data-toggle="tab"></a></li>
            <li role="presentation" class="hidden"><a href="#pois_tab" data-toggle="tab"></a></li>
            <li role="presentation" class="hidden"><a href="#pois_create" data-toggle="tab"></a></li>
            <li role="presentation" class="hidden"><a href="#pois_edit" data-toggle="tab"></a></li>
        </ul>

        <div class="op-header__actions">
            @yield('panel_actions')
        </div>
        </div>

        @yield('items')
    </div>
</div>

<a class="btn-sidebar-expand" onclick="app.changeSetting('toggleSidebar');" title="{{ trans('front.objects') }}">
    <i class="icon icon-fa fa-bars"></i>
</a>

<div id="mapWrap">

    <div id="map"></div>
    
    <div id="map-controls">
    <div id="map-controls-layers">
            <div class="btn-group-vertical" role="group" data-toggle="buttons">
                <label class="btn">
                    <input id="clusterDevice" type="checkbox" autocomplete="off" onchange="app.changeSetting('clusterDevice', this.checked);">
                    <span class="icon group-devices"></span>
                </label>
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.fit_objects')!!}">
                    <input id="fitBounds" type="checkbox" autocomplete="off" onchange="app.devices.toggleFitBounds(this.checked);">
                    <span class="icon fitBounds"></span>
                </label>
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.objects')!!}">
                @include('Frontend.Layouts.partials.map-follow-control')
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.objects')!!}">
                    <input id="showDevice" type="checkbox" autocomplete="off" onchange="app.changeSetting('showDevice', this.checked);">
                    <span class="icon devices"></span>
                </label>
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.geofences')!!}">
                    <input id="showGeofences" type="checkbox" autocomplete="off" onchange="app.changeSetting('showGeofences', this.checked);">
                    <span class="icon geofences"></span>
                </label>
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.routes')!!}">
                    <input id="showRoutes" type="checkbox" autocomplete="off" onchange="app.changeSetting('showRoutes', this.checked);">
                    <span class="icon routes"></span>
                </label>
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.poi')!!}">
                    <input id="showPoi" type="checkbox" autocomplete="off" onchange="app.changeSetting('showPoi', this.checked);">
                    <span class="icon poi"></span>
                </label>
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.show_names')!!}">
                    <input id="showNames" type="checkbox" autocomplete="off" onchange="app.changeSetting('showNames', this.checked);">
                    <span class="icon show-name"></span>
                </label>
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.show_tails')!!}">
                    <input id="showTail" type="checkbox" autocomplete="off" onchange="app.changeSetting('showTail', this.checked);">
                    <span class="icon show-tail"></span>
                </label>
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.live_traffic')!!}">
                    <input id="showTraffic" type="checkbox" autocomplete="off" onchange="app.changeSetting('showTraffic', this.checked);">
                    <span class="icon traffic"></span>
                </label>

                @if (config('addon.html_geolocation'))
                    <button type="button" class="btn" onclick="app.getMyLocation();"><span class="icon icon-fa fa-dot-circle-o"></span></button>
                @endif
            </div>
        </div>
        <div>
            <div class="btn-group-vertical" role="group">
                <button type="button" class="btn" onclick="app.zoomIn();"><span class="icon zoomIn"></span></button>
                <button type="button" class="btn" onclick="app.zoomOut();"><span class="icon zoomOut"></span></button>
            </div>
        </div>

        <div>
            <div class="btn-group-vertical" role="group">
                <button type="button" class="btn" onclick="app.mapFull();">
                    <span class="icon map-expand"></span>
                </button>
            </div>
        </div>

        <div>
            <div class="btn-group-vertical" data-position="fixed" role="group">
                <button type="button" class="btn" onClick="$('.leaflet-control-layers').toggleClass('leaflet-control-layers-expanded');">
                    <span class="icon map-layers"></span>
                </button>
            </div>
        </div>

        

   
        <div id="history-control-layers" style="display: none;">
            <div class="btn-group-vertical" role="group" data-toggle="buttons">
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.route')!!}">
                    <input id="showHistoryRoute" type="checkbox" autocomplete="off" onchange="app.changeSetting('showHistoryRoute', this.checked);">
                    <span class="icon routes"></span>
                </label>
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.arrows')!!}">
                    <input id="showHistoryArrow" type="checkbox" autocomplete="off" onchange="app.changeSetting('showHistoryArrow', this.checked);">
                    <span class="icon device"></span>
                </label>
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.stops')!!}">
                    <input id="showHistoryStop" type="checkbox" autocomplete="off" onchange="app.changeSetting('showHistoryStop', this.checked);">
                    <span class="icon parking"></span>
                </label>
                <label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.events')!!}">
                    <input id="showHistoryEvent" type="checkbox" autocomplete="off" onchange="app.changeSetting('showHistoryEvent', this.checked);">
                    <span class="icon event"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<a class="ajax-popup-link hidden"></a>
<input id="upload_file" type="file" style="display: none;" onchange=""/>

@include('Frontend.Layouts.partials.trans')

@yield('self-scripts')
<script type="text/javascript">
  $('.nav__list').find('li').has('ul').children('a').on('click', function (e) {
        e.preventDefault();

        // Collapsible
        $(this).parent('li').not('.disabled').not($('.sidebar-xs').not('.sidebar-xs-indicator').find('.nav__list').children('li')).toggleClass('active').children('ul').slideToggle(250);

        // Accordion
        if ($('.nav__list').hasClass('navigation-accordion')) {
            $(this).parent('li').not('.disabled').not($('.sidebar-xs').not('.sidebar-xs-indicator').find('.nav__list').children('li')).siblings(':has(.has-ul)').removeClass('active').children('ul').slideUp(250);
        }
    });

        
    // Alternate navigation
    $('.navigation-alt').find('li').has('ul').children('a').on('click', function (e) {
        e.preventDefault();

        // Collapsible
        $(this).parent('li').not('.disabled').toggleClass('active').children('ul').slideToggle(200);

        // Accordion
        if ($('.navigation-alt').hasClass('navigation-accordion')) {
            $(this).parent('li').not('.disabled').siblings(':has(.has-ul)').removeClass('active').children('ul').slideUp(200);
        }
    }); 

 // Main navigation
    // -------------------------

    // Add 'active' class to parent list item in all levels
    $('.navigation').find('li.active').parents('li').addClass('active');

    // Hide all nested lists
    $('.navigation').find('li').not('.active, .category-title').has('ul').children('ul').addClass('hidden-ul');

    // Highlight children links
    $('.navigation').find('li').has('ul').children('a').addClass('has-ul');

    // Add active state to all dropdown parent levels
    $('.dropdown-menu:not(.dropdown-content), .dropdown-menu:not(.dropdown-content) .dropdown-submenu').has('li.active').addClass('active').parents('.navbar-nav .dropdown:not(.language-switch), .navbar-nav .dropup:not(.language-switch)').addClass('active');


</script>

<script src="{{ asset_resource('assets/js/core.js') }}" type="text/javascript"></script>
<script src="{{ asset_resource('assets/js/gps-smooth.js') }}" type="text/javascript"></script>
<script src="{{ asset_resource('assets/js/app.js') }}" type="text/javascript"></script>

<div id="bottombar">
   @include('Frontend.Widgets.index')
</div>
<div id="bottombar-history">

@include('Frontend.History.bottom')


</div>

<div id="conversations"></div>


<script type="text/javascript">
    var handlers = L.drawLocal.draw.handlers;
    handlers.polygon.tooltip.start = '{{ trans('front.click_to_start_drawing_shape') }}';
    handlers.polygon.tooltip.cont = '{{ trans('front.click_to_continue_drawing_shape') }}';
    handlers.polygon.tooltip.end = '{{ trans('front.click_first_point_to_close_this_shape') }}';
    handlers.polyline.error = '{{ trans('front.shape_edges_cannot_cross') }}';
    handlers.polyline.tooltip.start = '{{ trans('front.click_to_start_drawing_line') }}';
    handlers.polyline.tooltip.cont = '{{ trans('front.click_to_continue_drawing_line') }}';
    handlers.polyline.tooltip.end = '{{ trans('front.click_last_point_to_finish_line') }}';

    handlers.circle.radius = '{{ trans('front.radius') }}';
    handlers.circle.tooltip.start = '{{ trans('front.click_and_drag_to_draw_shape') }}';
    handlers.simpleshape.tooltip.end = '{{ trans('front.release_mouse_to_finish_drawing') }}';

</script>

@yield('scripts')

@include('Frontend.Layouts.partials.app')

<script type="text/javascript">
    $(window).on("load", function() {
        app.init();
        @if($dashboard)
            app.dashboard.init();
        @endif
    });
</script>

<div class="modal" id="js-confirm-link" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                loading
            </div>
            <div class="modal-footer" style="margin-top: 0">
                <button type="button" value="confirm" class="btn btn-primary btn-main submit js-confirm-link-yes">{{ trans('admin.confirm') }}</button>
                <button type="button" value="cancel" class="btn btn-side" data-dismiss="modal">{{ trans('admin.cancel') }}</button>
            </div>
        </div>
    </div>
</div>

@include('Frontend.Popups.index')
</main>
</body>
</html>

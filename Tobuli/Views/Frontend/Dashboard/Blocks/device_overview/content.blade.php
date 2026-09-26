

<div class="row">
    @if(Auth::user()->perm('events', 'view'))
        <div class="col-sm-6">
        @include("Frontend.Dashboard.Blocks.device_overview.graph") 
        </div>
    @endif

    <div class="col-sm-6">
    @include("Frontend.Dashboard.Blocks.device_overview.events")
    </div>
</div>

<script type='text/javascript'>
    if ( typeof _static_device_overview === "undefined") {
        var _static_device_overview = true;
    }

    if (_static_device_overview && $('#dashboard').is(':visible')) {
        _static_device_overview = false;
        setTimeout(function () {
            _static_device_overview = true;
            app.dashboard.loadBlockContent('device_overview', true);
        }, 10000);
    }
</script>

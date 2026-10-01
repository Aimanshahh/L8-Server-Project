{{-- Follow toggle for the live map: pans smoothly to keep the selected device in view --}}
<label class="btn" data-toggle="tooltip" data-placement="left" title="{!!trans('front.follow')!!}">
    <input id="followDevice" type="checkbox" autocomplete="off" onchange="app.devices.toggleFollow(this.checked);">
    <span class="icon icon-fa fa-crosshairs"></span>
</label>

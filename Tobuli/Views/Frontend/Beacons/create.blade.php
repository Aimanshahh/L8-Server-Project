<?php $item = new \Tobuli\Entities\Device(); ?>
@extends('Frontend.Layouts.modal')

@section('title')
    <i class="icon device"></i> {!!trans('global.add_new') !!}
@stop

@section('body')
    <ul class="nav nav-tabs nav-default" role="tablist">
        <li class="active"><a href="#device-add-form-main" role="tab" data-toggle="tab">{!!trans('front.main') !!}</a></li>
        @if (isAdmin() && Auth::User()->can('view', new \Tobuli\Entities\User()))
            <li><a href="#device-add-form-users" role="tab" data-toggle="tab">{!!trans('front.users')!!}</a></li>
        @endif
        <li><a href="#device-add-form-icons" role="tab" data-toggle="tab">{!!trans('front.icons') !!}</a></li>
        <li><a href="#device-add-form-advanced" role="tab" data-toggle="tab">{!!trans('front.advanced') !!}</a></li>
        @if (isAdmin())
            <li><a href="#device-add-form-sensors" role="tab" data-toggle="tab">{{ trans('front.sensors') }}</a></li>
        @endif
        <li><a href="#device-add-form-tail" role="tab" data-toggle="tab">{!!trans('front.tail') !!}</a></li>
    </ul>

    {!! Form::open(['route' => 'beacons.store', 'method' => 'POST']) !!}
    {!! Form::hidden('id') !!}
    <div class="tab-content">
        <div id="device-add-form-main" class="tab-pane active">
            @if (isAdmin())
            <div class="form-group">
                <div class="checkbox-inline">
                    {!! Form::hidden('active', 0) !!}
                    {!! Form::checkbox('active', 1, true) !!}
                    {!! Form::label(null, trans('validation.attributes.active')) !!}
                </div>
            </div>
            @endif

            <div class="form-group">
                {!! Form::label('name', trans('validation.attributes.name').'*:') !!}
                {!! Form::text('name', null, ['class' => 'form-control']) !!}
            </div>

            @if(Auth::user()->can('edit', $item, 'imei'))
                <div class="form-group">
                    <label for="imei">{{ trans('front.tracker_id') }}:</label>
                    {!! Form::text('imei', null, ['class' => 'form-control'] ) !!}
                </div>
            @endif
        </div>

        @if (isAdmin() && Auth::User()->can('view', new \Tobuli\Entities\User()))
            <div id="device-add-form-users" class="tab-pane">
                <div class="form-group">
                    {!! Form::label('user_id', trans('validation.attributes.user').':') !!}
                    {!! Form::select('user_id[]', [], auth()->user()->id, [
                        'class' => 'form-control multiexpand half',
                        'multiple' => 'multiple',
                        'data-live-search' => 'true',
                        'data-actions-box' => 'true',
                        'data-ajax' => route('devices.users.index')
                        ]) !!}
                </div>
            </div>
        @endif

        <div id="device-add-form-icons" class="tab-pane">
            @php $defaultIcon = \Tobuli\Entities\DeviceIcon::find(settings("device.icon_id")); @endphp
            <div class="form-group">
                {!! Form::label('device_icons_type', trans('validation.attributes.icon_type').':') !!}
                {!! Form::select('device_icons_type', $icons_type, null, ['class' => 'form-control']) !!}
            </div>

            {!! Form::hidden('icon_id', 0) !!}
            @foreach($device_icons_grouped as $group => $icons)
                <div data-disablable="#device_icons_type;hide-disable;{{ $group }}">
                    <div class="form-group">
                        {!! Form::label(null, trans('validation.attributes.icon_id').':') !!}
                    </div>

                    <div class="icon-list">
                        @foreach($icons as $icon)
                            <div class="checkbox-inline">
                                {!! Form::radio('icon_id', $icon->id, $icon->id == ($defaultIcon->id ?? 0)) !!}
                                <label>
                                    <img src="{!!asset($icon->path) !!}" alt="ICON" style="width: {!!$icon->width!!}px; height: {!!$icon->height!!}px;" />
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div data-disablable="#device_icons_type;hide;arrow">
                <div class="form-group">
                    {!! Form::label('icon_moving', trans('front.moving').':') !!}
                    {!! Form::select('icon_moving', $device_icon_colors, settings('device.status_colors.colors.moving'), ['class' => 'form-control']) !!}
                </div>
                <div class="form-group">
                    {!! Form::label('icon_stopped', trans('front.stopped').':') !!}
                    {!! Form::select('icon_stopped', $device_icon_colors, settings('device.status_colors.colors.stopped'), ['class' => 'form-control']) !!}
                </div>
                <div class="form-group">
                    {!! Form::label('icon_offline', trans('front.offline').':') !!}
                    {!! Form::select('icon_offline', $device_icon_colors, settings('device.status_colors.colors.offline'), ['class' => 'form-control']) !!}
                </div>
                <div class="form-group">
                    {!! Form::label('icon_engine', trans('front.engine_idle').':') !!}
                    {!! Form::select('icon_engine', $device_icon_colors, settings('device.status_colors.colors.engine'), ['class' => 'form-control']) !!}
                </div>

                @if (\Tobuli\Sensors\Types\Blocked::isEnabled())
                    <div class="form-group">
                        {!! Form::label('icon_blocked', trans('front.blocked').':') !!}
                        {!! Form::select('icon_blocked', $device_icon_colors, settings('device.status_colors.colors.blocked'), ['class' => 'form-control']) !!}
                    </div>
                @endif
            </div>
        </div>

        <div id="device-add-form-advanced" class="tab-pane">
            <div class="form-group">
                {!! Form::label('group_id', trans('validation.attributes.group_id').':') !!}
                {!! Form::select('group_id', $device_groups, null, ['class' => 'form-control', 'data-live-search' => 'true']) !!}
            </div>

            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!!Form::label('vin', trans('validation.attributes.vin').':')!!}
                        {!!Form::text('vin', null, ['class' => 'form-control'])!!}
                    </div>
                    <div class="form-group">
                        {!!Form::label('device_model', trans('validation.attributes.device_model').':')!!}
                        {!!Form::text('device_model', null, ['class' => 'form-control'])!!}
                    </div>
                    <div class="form-group">
                        {!!Form::label('object_owner', trans('validation.attributes.object_owner').':')!!}
                        {!!Form::text('object_owner', null, ['class' => 'form-control'])!!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!!Form::label('plate_number', trans('validation.attributes.plate_number').':')!!}
                        {!!Form::text('plate_number', null, ['class' => 'form-control'])!!}
                    </div>
                    <div class="form-group">
                        {!!Form::label('registration_number', trans('validation.attributes.registration_number').':')!!}
                        {!!Form::text('registration_number', null, ['class' => 'form-control'])!!}
                    </div>

                </div>
            </div>

            <div class="form-group">
                {!!Form::label('additional_notes', trans('validation.attributes.additional_notes').':')!!}
                {!!Form::text('additional_notes', null, ['class' => 'form-control'])!!}
            </div>
            <div class="form-group">
                {!!Form::label('comment', trans('validation.attributes.comment').':')!!}
                {!!Form::text('comment', null, ['class' => 'form-control'])!!}
            </div>
        </div>

        <div id="device-add-form-sensors" class="tab-pane">
            <div class="form-group">
                {!! Form::label('sensor_group_id', trans('validation.attributes.sensor_group_id').':') !!}
                {!! Form::select('sensor_group_id', $sensor_groups, null, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div id="device-add-form-tail" class="tab-pane">
            <div class="form-group">
                {!! Form::label('tail_color', trans('validation.attributes.tail_color').':') !!}
                {!! Form::text('tail_color', settings('device.tail.color'), ['class' => 'form-control colorpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('tail_length', trans('validation.attributes.tail_length').' (0-10 '.trans('front.last_points').'):') !!}
                {!! Form::text('tail_length', settings('device.tail.length'), ['class' => 'form-control']) !!}
            </div>
        </div>
    </div>
    {!! Form::close() !!}

    <script>
        $(document).ready(function() {
            $('select[name="device_icons_type"]').trigger('change');
        });
    </script>
@stop
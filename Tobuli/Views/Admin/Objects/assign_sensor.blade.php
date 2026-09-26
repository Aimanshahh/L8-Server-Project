@extends('Frontend.Layouts.modal')

@section('title')
    {{ trans('global.add') }}
@stop

@section('body')
    {!!Form::open(['route' => 'admin.objects.sensor', 'method' => 'POST'])!!}
    @foreach($device_id as $id)
        {!!Form::hidden('device_id[]', $id)!!}
    @endforeach

   

    <div class="form-group">
        {!! Form::label('user_id', trans('validation.attributes.user').'*:') !!}
        {!! Form::select('user_id[]', $users->pluck('email', 'id'), null, ['class' => 'form-control', 'multiple' => 'multiple', 'data-live-search' => 'true']) !!}
    </div>
    <div class="form-group">
                {!! Form::label('sensor_group_id', trans('validation.attributes.sensor_group_id').':') !!}
                {!! Form::select('sensor_group_id', $sensor_groups, null, ['class' => 'form-control']) !!}
            </div>

    {!!Form::close()!!}
@stop

@section('buttons')
    <a type="button" class="btn btn-danger" data-submit="modal">{{ trans('admin.confirm') }}</a>
    <a type="button" class="btn btn-default" data-dismiss="modal">{{ trans('admin.cancel') }}</a>
@stop
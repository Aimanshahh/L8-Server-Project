@extends('Admin.Layouts.modal')

@section('modal_class', 'modal-sm')

@section('icon')
    <i class="fas fa-envelope"></i>
@stop

@section('title')
    <i class="icon edit"></i> {{ trans('global.add') }}
@stop

@section('subtitle')
    Choose which email template to create
@stop

@section('body')
    {!! Form::open(array('route' => 'admin.email_templates.store', 'method' => 'POST')) !!}
        <!-- title field -->
        <div class="form-group">
            {!! Form::label('name', trans('validation.attributes.name').':') !!}
            {!! Form::select('name', $names, null, ['class' => 'form-control']) !!}
        </div>
    {!! Form::close() !!}
@stop
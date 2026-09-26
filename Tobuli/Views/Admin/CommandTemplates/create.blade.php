@extends('Frontend.Layouts.modal')

@section('title', trans('global.edit'))

@section('body')
    {!! Form::open(['route' => 'admin.command_templates.store', 'method' => 'POST']) !!}

    @include('Admin.CommandTemplates.form')

    {!! Form::close() !!}
@stop
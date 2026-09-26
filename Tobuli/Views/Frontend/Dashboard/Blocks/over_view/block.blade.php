@extends('Frontend.Dashboard.Blocks.layout')

@section('width', 'col-md-12 auto-height')

@section('header')
    <a href="javascript:" onclick="app.dashboard.close()" style="text-decoration: none !important;color:#000;">
        <i class="icon device"></i> {{ trans('front.overview') }}
    </a>
@stop

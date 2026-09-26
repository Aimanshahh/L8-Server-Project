@extends('Frontend.Dashboard.Blocks.layout-2')

@section('header')
    <a href="javascript:" onclick="app.dashboard.close()" style="text-decoration: none !important;">
        <i class="icon device"></i>
        {{ trans('front.devices_activity_percents') }}
    </a>
@stop
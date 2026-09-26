@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-settings-overrides.css') }}">
@stop

@section('content')
<div class="st-page">

    <div class="st-page__header">
        <div class="st-page__title-group">
            <div class="st-page__title-icon"><i class="fas fa-screwdriver-wrench"></i></div>
            <div>
                <h1 class="st-page__title">{{ trans('front.tools') }}</h1>
                <p class="st-page__subtitle">Scheduled maintenance: database backups, and how long device history is kept.</p>
            </div>
        </div>
    </div>

    @if (Session::has('errors'))
        <div class="alert alert-danger">
            <ul>
                @foreach (Session::get('errors')->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="st-grid">
        @foreach($tools as $tool)
            {!! $tool !!}
        @endforeach
    </div>
</div>
@stop

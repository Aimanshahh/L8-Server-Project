@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-settings-overrides.css') }}">
@stop

@section('content')
<div class="st-page">

    <div class="st-page__header">
        <div class="st-page__title-group">
            <div class="st-page__title-icon"><i class="fas fa-server"></i></div>
            <div>
                <h1 class="st-page__title">{{ trans('front.main_server_settings') }}</h1>
                <p class="st-page__subtitle">Server identity, default map providers, localisation and branding.</p>
            </div>
        </div>
    </div>

    @if (Session::has('errors'))
        <div class="alert alert-danger">
            <ul>
                @foreach (Session::get('errors')->all() as $error)
                    <li>{!! $error !!}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (Auth::User()->isReseller())
        <div class="alert alert-info">
            {{ trans('admin.your_branding_url') }}: {{ route('login', Auth::User()->id) }}
        </div>
    @endif

    <div class="st-grid">
        <div>
            @if (Auth::User()->isAdmin())
                @include('Admin.MainServerSettings.partials.main')
            @else
                @include('Admin.MainServerSettings.partials.manager')
            @endif
        </div>

        <div>
            @include('Admin.MainServerSettings.partials.appearance')
        </div>
    </div>
</div>
@stop

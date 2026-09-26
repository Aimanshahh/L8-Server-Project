@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-logs-overrides.css') }}?v=20260925-1">
@stop

@section('content')
    <div class="al-page">
        <div class="al-page__header">
            <div class="al-page__title-group">
                <div class="al-page__title-icon"><i class="fas fa-file-lines"></i></div>
                <div>
                    <h1 class="al-page__title">{{ trans('admin.tracker_logs') }}</h1>
                    <p class="al-page__subtitle">Log files written by the tracking server</p>
                </div>
            </div>

            <div class="al-page__actions">
                <a href="javascript:" class="al-ghost" data-modal="logs_config"
                   data-url="{{ route('admin.logs.config.get') }}">
                    <i class="fas fa-gear"></i> {{ trans('front.settings') }}
                </a>
            </div>
        </div>

        <div class="al-card" id="table_{{ $section }}">
            <div class="panel-body" data-table>
                @include('Admin.'.ucfirst($section).'.table')
            </div>
        </div>
    </div>
@stop

@section('javascript')
<script>
    tables.set_config('table_{{ $section }}', {
        url:'{{ route("admin.{$section}.index") }}',
        delete_url:'{{ route("admin.{$section}.delete") }}'
    });
</script>
@stop

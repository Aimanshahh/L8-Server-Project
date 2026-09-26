@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-logs-overrides.css') }}?v=20260925-1">
@stop

@section('content')
    <div class="al-page">
        <div class="al-page__header">
            <div class="al-page__title-group">
                <div class="al-page__title-icon"><i class="fas fa-user-secret"></i></div>
                <div>
                    <h1 class="al-page__title">{{ trans('admin.unregistered_devices_log') }}</h1>
                    <p class="al-page__subtitle">Devices that sent data from an IMEI with no matching object</p>
                </div>
            </div>
        </div>

        <div class="al-card" id="table_unregistered_devices_log">

            <div class="al-toolbar">
                <div class="al-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search_phrase" value="{{ $search ?? '' }}"
                           placeholder="Search IMEI, IP or port..." data-filter autocomplete="off">
                </div>

                <div class="lg-summary">
                    <span class="al-toolbar__count">{{ number_format($total ?? 0) }} IMEIs</span>
                    <span class="al-toolbar__count">{{ number_format($attempts ?? 0) }} attempts</span>
                    <a href="{{ route('admin.unregistered_devices_log.index') }}" class="al-ghost">
                        <i class="fas fa-rotate"></i> Reset
                    </a>
                </div>
            </div>

            <div class="panel-body" data-table>
                @include('Admin.UnregisteredDevicesLog.table')
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        tables.set_config('table_unregistered_devices_log', {
            url:'{{ route("admin.unregistered_devices_log.index") }}',
            delete_url:'{{ route("admin.unregistered_devices_log.destroy") }}'
        });
    </script>
@stop

@extends('admin::Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/sensor-icons-overrides.css') }}?v=20260918-2">
@stop

@section('content')
<div class="sic-page">

    <div class="sic-page__header">
        <div class="sic-page__title-group">
            <div class="sic-page__title-icon"><i class="fas fa-gauge-high"></i></div>
            <div>
                <h1 class="sic-page__title">{!! trans('admin.sensor_icons') !!}</h1>
                <p class="sic-page__subtitle">Icons used to represent device sensors and their values</p>
            </div>
        </div>

        <div class="sic-page__actions">
            <a href="javascript:" class="sic-add"
               data-modal="sensor_icons_create"
               data-url="{{ route('admin.sensor_icons.create') }}">
                <i class="fas fa-plus"></i> {{ trans('global.add') }}
            </a>
        </div>
    </div>

    <div class="sic-card" id="table_sensor_icons">
        <div class="panel-heading">
            <div class="panel-title">{{ count($items) }} icon{{ count($items) == 1 ? '' : 's' }}</div>
        </div>
        <div class="panel-body" data-table>
            @include('admin::SensorIcons.table')
        </div>
    </div>
</div>
@stop

@section('javascript')
    <script>
        tables.set_config('table_sensor_icons', {
            url: '{{ route("admin.sensor_icons.index") }}',
            delete_url:'{{ route("admin.sensor_icons.destroy") }}'
        });

        function sensor_icons_edit_modal_callback() {
            tables.get('table_sensor_icons');
        }

        function sensor_icons_create_modal_callback() {
            tables.get('table_sensor_icons');
        }

        $(document).ready(function() {
            $(document).on('click', '.table-icon .controls a', function () {
                $.ajax({
                    type: 'POST',
                    url: '{{ route("admin.sensor_icons.destroy") }}',
                    data: {
                        _method: 'DELETE',
                        id: {0:$(this).data('id')}
                    },
                    success: function () {
                        tables.get('table_sensor_icons');
                    }
                });
            });
        });
    </script>
@stop

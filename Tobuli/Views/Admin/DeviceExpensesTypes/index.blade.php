@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-list-overrides.css') }}?v=20260918-1">
@stop

@section('content')
<div class="al-page">

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-receipt"></i></div>
            <div>
                <h1 class="al-page__title">{!! trans('admin.expenses_types') !!}</h1>
                <p class="al-page__subtitle">Expense categories that can be recorded against devices</p>
            </div>
        </div>

        <div class="al-page__actions">
            <a href="javascript:" class="al-add"
               data-modal="device_expenses_types_create"
               data-url="{{ route('admin.device_expenses_types.create') }}">
                <i class="fas fa-plus"></i> {{ trans('global.add') }}
            </a>
        </div>
    </div>

    <div class="al-card" id="table_device_expenses_types">
        <div class="al-toolbar">
            <div class="al-toolbar__count">
                {{ $types->total() }} {{ trans('admin.expenses_types') }}
            </div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.DeviceExpensesTypes.table')
        </div>
    </div>
</div>
@stop

@section('javascript')
    <script>
        tables.set_config('table_device_expenses_types', {
            url:'{{ route("admin.device_expenses_types.index") }}',
            delete_url:'{{ route("admin.device_expenses_types.destroy") }}'
        });

        function device_expenses_types_edit_modal_callback() {
            tables.get('table_device_expenses_types');
        }

        function device_expenses_types_create_modal_callback() {
            tables.get('table_device_expenses_types');
        }
    </script>
@stop

@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-list-overrides.css') }}?v=20260918-2">
@stop

@section('content')
<div class="al-page">

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-tower-broadcast"></i></div>
            <div>
                <h1 class="al-page__title">{!! trans('front.apn_configuration') !!}</h1>
                <p class="al-page__subtitle">APN profiles pushed to devices when configuring mobile data</p>
            </div>
        </div>

        <div class="al-page__actions">
            <a href="javascript:" class="al-add"
               data-modal="apn_config_create"
               data-url="{{ route('admin.apn_config.create') }}">
                <i class="fas fa-plus"></i> {{ trans('global.add') }}
            </a>
        </div>
    </div>

    <div class="al-card" id="table_apn_config">
        <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
        <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

        <div class="al-toolbar">
            <div class="al-search">
                <i class="fas fa-search"></i>
                {!! Form::text('search_phrase', null, [
                    'class' => 'form-control',
                    'placeholder' => trans('admin.search_it'),
                    'data-filter' => 'true',
                    'autocomplete' => 'off',
                ]) !!}
            </div>

            <div class="al-toolbar__count">
                {{ trans('global.total') }}: {{ $items->total() }}
            </div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.ApnConfig.table')
        </div>
    </div>
</div>
@stop

@section('javascript')
<script>
    tables.set_config('table_apn_config', {
        url:'{{ route("admin.apn_config.index") }}',
    });

    function apn_config_edit_modal_callback() {
        tables.get('table_apn_config');
    }

    function apn_config_create_modal_callback() {
        tables.get('table_apn_config');
    }
</script>
@stop

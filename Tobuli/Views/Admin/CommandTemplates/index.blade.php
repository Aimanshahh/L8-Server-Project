@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-list-overrides.css') }}?v=20260918-4">
@stop

@section('content')
<div class="al-page">

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-terminal"></i></div>
            <div>
                <h1 class="al-page__title">{!! trans('front.command_templates') !!}</h1>
                <p class="al-page__subtitle">Reusable commands sent to devices over GPRS or SMS</p>
            </div>
        </div>

        <div class="al-page__actions">
            <a href="javascript:" class="al-add"
               data-modal="command_templates_create"
               data-url="{{ route('admin.command_templates.create') }}">
                <i class="fas fa-plus"></i> {{ trans('admin.add_new') }}
            </a>
        </div>
    </div>

    <div class="al-card" id="table_command_templates">

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
            @include('Admin.CommandTemplates.table')
        </div>
    </div>
</div>
@stop

@section('javascript')
    <script>
        tables.set_config('table_command_templates', {
            url:'{{ route("admin.command_templates.index") }}',
            delete_url:'{{ route("admin.command_templates.destroy") }}'
        });
        function command_templates_edit_modal_callback() {
            tables.get('table_command_templates');
        }
        function command_templates_create_modal_callback() {
            tables.get('table_command_templates');
        }
    </script>
@stop

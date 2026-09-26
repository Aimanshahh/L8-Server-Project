@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/bootstrap-wysihtml5/bootstrap-wysihtml5.css') }}"/>
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-list-overrides.css') }}?v=20260918-1">
@stop

@section('content')
<div class="al-page">

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-file-lines"></i></div>
            <div>
                <h1 class="al-page__title">{!! trans('admin.pages') !!}</h1>
                <p class="al-page__subtitle">Custom content pages published on your platform</p>
            </div>
        </div>

        <div class="al-page__actions">
            <a href="javascript:" class="al-add"
               data-modal="pages_create"
               data-url="{{ route('admin.pages.create') }}">
                <i class="fas fa-plus"></i> {{ trans('admin.add_new') }}
            </a>
        </div>
    </div>

    <div class="al-card" id="table_pages">

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
                {{ $items->total() }} {{ trans('admin.pages') }}
            </div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.Pages.table')
        </div>
    </div>
</div>
@stop

@section('javascript')
    <script src="{{ asset('assets/plugins/bootstrap-wysihtml5/wysihtml5-0.3.0.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/plugins/bootstrap-wysihtml5/bootstrap-wysihtml5.js') }}" type="text/javascript"></script>

    <script>
        tables.set_config('table_pages', {
            url:'{{ route("admin.pages.table") }}',
            delete_url:'{{ route("admin.pages.destroy") }}'
        });
        function pages_edit_modal_callback() {
            tables.get('table_pages');
        }
        function pages_create_modal_callback() {
            tables.get('table_pages');
        }
    </script>
@stop

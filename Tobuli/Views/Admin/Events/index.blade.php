@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-list-overrides.css') }}?v=20260918-4">
@stop

@section('content')
<div class="al-page">

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-bell"></i></div>
            <div>
                <h1 class="al-page__title">{!! trans('admin.'.$section) !!}</h1>
                <p class="al-page__subtitle">Protocol events reported by devices and the parameters that trigger them</p>
            </div>
        </div>

        <div class="al-page__actions">
            <a href="javascript:" class="al-add"
               data-modal="{{ $section }}_create"
               data-url="{{ route("admin.{$section}.create") }}">
                <i class="fas fa-plus"></i> {{ trans('admin.add_new') }}
            </a>
        </div>
    </div>

    <div class="al-card" id="table_{{ $section }}">
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
            @include('Admin.'.ucfirst($section).'.table')
        </div>
    </div>
</div>
@stop

@section('javascript')
<script>
    tables.set_config('table_{{ $section }}', {
        url:'{{ route("admin.{$section}.index") }}',
        delete_url:'{{ route("admin.{$section}.destroy") }}'
    });

    function {{ $section }}_edit_modal_callback() {
        tables.get('table_{{ $section }}');
    }

    function {{ $section }}_create_modal_callback() {
        tables.get('table_{{ $section }}');
    }
</script>
@stop

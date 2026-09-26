@extends('admin::Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-logs-overrides.css') }}?v=20260925-1">
@stop

@section('content')
    <div class="al-page">
        <div class="al-page__header">
            <div class="al-page__title-group">
                <div class="al-page__title-icon"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <h1 class="al-page__title">{{ trans('admin.model_change_logs') }}</h1>
                    <p class="al-page__subtitle">Creates, updates, deletes and logins recorded by the system</p>
                </div>
            </div>

            <div class="al-page__actions">
                <a href="javascript:" class="al-ghost" id="csv_export">
                    <i class="fas fa-download"></i> {{ trans('front.export_csv') }}
                </a>
            </div>
        </div>

        <div class="al-card" id="table_model_changes">

            <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
            <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

            <div class="al-toolbar lg-toolbar">
                <div class="al-search">
                    <i class="fas fa-search"></i>
                    {!! Form::text('search_phrase', null, [
                            'class' => 'form-control',
                            'placeholder' => trans('admin.search_it'),
                            'data-filter' => 'true']) !!}
                </div>

                <div class="lg-filters">
                    <div class="lg-filter lg-filter--user">
                        {!! Form::select('search_causer', [], $items->sorting['causer'] ?? null, [
                                'class' => 'form-control',
                                'title' => trans('global.user'),
                                'data-live-search' => 'true',
                                'data-actions-box' => 'true',
                                'data-ajax' => route('devices.users.index'),
                                'data-filter' => 'true']) !!}
                    </div>
                    <div class="lg-filter lg-filter--action">
                        {!! Form::select('search_descriptions[]', $descriptions, $items->sorting['descriptions'] ?? null, [
                                'class' => 'form-control',
                                'multiple' => 'multiple',
                                'title' => trans('front.action'),
                                'data-filter' => 'true']) !!}
                    </div>
                    <div class="lg-filter lg-filter--date">
                        {!! Form::text('search_date_from', null, [
                                'class' => 'form-control datetimepicker',
                                'data-filter' => 'true',
                                'placeholder' => trans('validation.attributes.date_from'),
                                'data-date-clear-btn' => 'true']) !!}
                    </div>
                    <div class="lg-filter lg-filter--date">
                        {!! Form::text('search_date_to', null, [
                                'class' => 'form-control datetimepicker',
                                'data-filter' => 'true',
                                'placeholder' => trans('validation.attributes.date_to'),
                                'data-date-clear-btn' => 'true']) !!}
                    </div>
                    @if(!empty($items->sorting['subjects']))
                        <div class="lg-filter lg-filter--subject">
                            {!! Form::search('search_subjects', $items->sorting['subjects'], [
                                    'id' => 'search_subjects',
                                    'class' => 'form-control',
                                    'readonly' => 'readonly',
                                ]) !!}
                            <span id="search_subjects_clear" class="fas fa-times"></span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="panel-body" data-table>
                @include('admin::ModelChangeLogs.table')
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        tables.set_config('table_model_changes', {
            url: '{{ request()->fullUrl() }}'
        });

        $("#search_subjects_clear").click(function() {
            let subjects = $("#search_subjects");

            if (!subjects.val()) {
                return;
            }

            subjects.val('');

            window.location.href = '{!! route('admin.model_change_logs.index') !!}';
        });

        $('#csv_export').click(function (e) {
            e.preventDefault();

            let queryParams = '';

            $('input[data-filter], select[data-filter]').each(function () {
                let value = $(this).val();
                let name = $(this).attr("name");

                if (name.startsWith('sorting')) {
                    return;
                }

                if (Array.isArray(value)) {
                    value.forEach(item => queryParams += '&' + name + '=' + item);
                } else {
                    queryParams += '&' + name + '=' + value;
                }
            });

            @if ($searchSubjects = request('search_subjects'))
                queryParams += '&search_subjects[]={!! implode('&search_subjects[]=', $searchSubjects) !!}';
            @endif

            window.location.href = "{!! route('admin.model_change_logs.export') !!}?" + queryParams;
        })
    </script>
@stop

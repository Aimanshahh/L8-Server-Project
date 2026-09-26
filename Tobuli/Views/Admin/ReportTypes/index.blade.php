@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-settings-overrides.css') }}">
@stop

@section('content')
<div class="st-page">

    <div class="st-page__header">
        <div class="st-page__title-group">
            <div class="st-page__title-icon"><i class="fas fa-file-lines"></i></div>
            <div>
                <h1 class="st-page__title">{{ trans('admin.report_types') }}</h1>
                <p class="st-page__subtitle">Choose which report types users can run. Each change is saved the moment you tick it.</p>
            </div>
        </div>
    </div>

    <div class="st-card" id="rt-card">

        <div class="st-toolbar">
            <div class="st-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="rt-search" placeholder="Search report types..." autocomplete="off">
            </div>

            <div class="st-toolbar__actions">
                <span class="st-count" id="rt-count"></span>
                <button type="button" class="st-btn st-btn--ghost st-btn--sm" id="rt-enable-all">
                    <i class="fas fa-check"></i> Enable shown
                </button>
                <button type="button" class="st-btn st-btn--ghost st-btn--sm" id="rt-disable-all">
                    <i class="fas fa-xmark"></i> Disable shown
                </button>
                <span class="st-save-state" id="rt-state"></span>
            </div>
        </div>

        <div class="st-checkgrid" id="rt-grid">
            @php /** @var \Tobuli\Reports\Report $report */ @endphp
            @foreach($reports as $id => $report)
                <label class="st-checkitem {{ $report->isReasonable() ? '' : 'is-locked' }}"
                       data-name="{{ $report->title() }}">
                    {!! Form::checkbox(
                            "reports[$id][status]",
                            1,
                            isset($user) ? $report->isUserEnabled($user) : $report->isEnabled(),
                            ['class' => 'report_status'] + ($report->isReasonable() ? [] : ['disabled' => 'disabled'])
                        ) !!}
                    <span class="st-checkitem__text">{{ $report->title() }}</span>
                    @if (! $report->isReasonable())
                        <span class="st-checkitem__lock" title="{{ trans('front.unsupported') }}">Unavailable</span>
                    @endif
                </label>
            @endforeach
        </div>

        <div class="st-empty is-hidden" id="rt-empty">No report type matches that search.</div>
    </div>
</div>
@stop

@section('javascript')
    <script>
        $(document).ready(function() {
            var saveUrl = '{!! route('admin.report_types.store') !!}';
            var $grid = $('#rt-grid');
            var $items = $grid.find('.st-checkitem');
            var $state = $('#rt-state');

            function syncItem(input) {
                $(input).closest('.st-checkitem').toggleClass('is-on', $(input).prop('checked'));
            }

            function counts() {
                var enabled = $items.filter('.is-on').length;
                var visible = $items.filter(':not(.is-hidden)').length;
                var text = '<strong>' + enabled + '</strong> / ' + $items.length + ' enabled';

                if (visible !== $items.length)
                    text += ' · <strong>' + visible + '</strong> shown';

                $('#rt-count').html(text);
                $('#rt-empty').toggleClass('is-hidden', visible !== 0);
            }

            function state(cls, text) {
                $state.removeClass('is-saving is-saved is-failed').addClass(cls).text(text);

                if (cls === 'is-saved')
                    setTimeout(function() { $state.removeClass('is-saved').text(''); }, 1800);
            }

            $items.each(function() { syncItem($(this).find('.report_status')); });
            counts();

            /* single row - same request this page always sent */
            $(document).on('change', '.report_status', function () {
                var input = this;
                var data = {};

                data[$(input).attr('name')] = +$(input).prop('checked');

                $(input).closest('.st-checkitem').addClass('is-saving');
                syncItem(input);
                counts();
                state('is-saving', 'Saving...');

                $.ajax({
                    type: 'POST',
                    dataType: 'html',
                    url: saveUrl,
                    data: data
                }).done(function() {
                    state('is-saved', 'Saved');
                }).fail(function() {
                    $(input).prop('checked', !$(input).prop('checked'));
                    syncItem(input);
                    counts();
                    state('is-failed', 'Could not save');
                }).always(function() {
                    $(input).closest('.st-checkitem').removeClass('is-saving');
                });
            });

            /* bulk - one request for every shown, selectable row */
            function bulk(checked) {
                var data = {};
                var $targets = $grid.find('.st-checkitem:not(.is-hidden) .report_status:not(:disabled)');

                if (!$targets.length)
                    return;

                $targets.each(function() {
                    data[$(this).attr('name')] = checked ? 1 : 0;
                });

                $grid.find('.st-checkitem:not(.is-hidden)').addClass('is-saving');
                state('is-saving', 'Saving...');

                $.ajax({
                    type: 'POST',
                    dataType: 'html',
                    url: saveUrl,
                    data: data
                }).done(function() {
                    $targets.prop('checked', checked).each(function() { syncItem(this); });
                    state('is-saved', 'Saved');
                }).fail(function() {
                    state('is-failed', 'Could not save');
                }).always(function() {
                    $grid.find('.st-checkitem').removeClass('is-saving');
                    counts();
                });
            }

            $('#rt-enable-all').on('click', function() { bulk(true); });
            $('#rt-disable-all').on('click', function() { bulk(false); });

            /* instant filter */
            $('#rt-search').on('input', function () {
                var q = $.trim($(this).val()).toLowerCase();

                $items.each(function() {
                    var hit = !q || $(this).data('name').toLowerCase().indexOf(q) !== -1;
                    $(this).toggleClass('is-hidden', !hit);
                });

                counts();
            });
        });
    </script>
@stop

@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-list-overrides.css') }}">
@stop

@section('content')
<div class="al-page">

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-globe"></i></div>
            <div>
                <h1 class="al-page__title">{!! trans('admin.languages') !!}</h1>
                <p class="al-page__subtitle">Turn a language on to let customers pick it, and choose the flag shown beside it.</p>
            </div>
        </div>
    </div>

    {{-- the toolbar sits outside [data-table] on purpose: the table plugin
         replaces that element's contents on every refresh --}}
    <div class="al-card al-card--scroll" id="table_{{ $section }}">

        <div class="al-toolbar">
            <div class="al-search">
                <i class="fas fa-search"></i>
                <input type="text" id="languages-search" placeholder="Search languages..." autocomplete="off">
            </div>

            <div class="al-filters" role="group">
                <button type="button" class="al-filter is-active" data-state="all">
                    All <span class="al-filter__count"></span>
                </button>
                <button type="button" class="al-filter" data-state="1">
                    {{ trans('validation.attributes.active') }} <span class="al-filter__count"></span>
                </button>
                <button type="button" class="al-filter" data-state="0">
                    {{ trans('front.inactive') }} <span class="al-filter__count"></span>
                </button>
            </div>

            <div class="al-toolbar__count" id="languages-count"></div>
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
        url:'{{ route("admin.{$section}.index") }}'
    });

    function {{ $section }}_edit_modal_callback() {
        tables.get('table_{{ $section }}');
    }

    /* -------- search and status filter, applied to the rendered rows -------- */
    var languagesFilter = (function () {
        var $scope = $('#table_{{ $section }}');

        function rows() {
            return $scope.find('table tbody tr[data-language]');
        }

        // the counts are written straight to the DOM: the chips live outside the
        // table the plugin rewrites, so nothing else can touch them
        function setCount(state, value) {
            var chip = document.querySelector('#table_{{ $section }} .al-filter[data-state="' + state + '"] .al-filter__count');

            if (chip)
                chip.textContent = String(value);
        }

        function apply() {
            var query = $.trim($('#languages-search').val() || '').toLowerCase();
            var state = ($scope.find('.al-filter.is-active').attr('data-state') || 'all') + '';
            var all = rows();
            var shown = 0;

            all.each(function () {
                var $row = $(this);
                var matchesText = !query || ($row.attr('data-search') + '').indexOf(query) !== -1;
                var matchesState = (state === 'all') || ($row.attr('data-state') + '' === state);
                var hit = matchesText && matchesState;

                $row.toggleClass('is-hidden', !hit);

                if (hit)
                    shown++;
            });

            $scope.find('.languages-none').toggleClass('is-hidden', shown !== 0 || all.length === 0);

            var counter = $('#languages-count').get(0);

            if (counter)
                counter.textContent = shown + ' of ' + all.length + ' languages';
        }

        function counts() {
            var all = rows();
            var active = 0;

            all.each(function () {
                if (String($(this).attr('data-state')) === '1')
                    active++;
            });

            setCount('all', all.length);
            setCount('1', active);
            setCount('0', all.length - active);
        }

        return {
            init: function () {
                $scope.on('input', '#languages-search', apply);

                $scope.on('click', '.al-filter', function () {
                    $(this).addClass('is-active').siblings().removeClass('is-active');
                    apply();
                });

                // the table plugin swaps the card's contents after an edit, so keep
                // the filter applied to whatever it renders
                if (window.MutationObserver) {
                    var target = $scope.find('[data-table]').get(0);

                    if (target) {
                        new MutationObserver(function () {
                            counts();
                            apply();
                        }).observe(target, {childList: true, subtree: true});
                    }
                }

                counts();
                apply();
            }
        };
    })();

    $(function () {
        languagesFilter.init();
    });
</script>
@stop

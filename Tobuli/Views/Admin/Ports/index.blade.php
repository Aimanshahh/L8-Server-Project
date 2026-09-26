@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-list-overrides.css') }}">
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-ports-overrides.css') }}">
@stop

@section('content')
<div class="al-page">

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-plug"></i></div>
            <div>
                <h1 class="al-page__title">{{ trans('admin.tracking_ports') }}</h1>
                <p class="al-page__subtitle">Every port the tracker protocols listen on, with the extra parameters each one accepts.</p>
            </div>
        </div>

        <div class="al-page__actions">
            <a href="javascript:" class="al-ghost"
               data-modal="update_config"
               data-url="{{ route('admin.ports.do_update_config') }}"
               title="{{ trans('admin.update_config_and') }}">
                <i class="icon restart"></i> {{ trans('admin.update_config_and') }}
            </a>
            <a href="javascript:" class="al-ghost"
               data-modal="update_config"
               data-url="{{ route('admin.ports.do_reset_default') }}"
               title="{{ trans('admin.reset_default') }}">
                <i class="icon reset"></i> {{ trans('admin.reset_default') }}
            </a>
        </div>
    </div>

    {{-- the toolbar sits outside [data-table] on purpose: the table plugin
         replaces that element's contents on every refresh --}}
    <div class="al-card al-card--scroll" id="table_ports">

        <div class="al-toolbar">
            <div class="al-search">
                <i class="fas fa-search"></i>
                <input type="text" id="ports-search" placeholder="Search by port or name..." autocomplete="off">
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

            <div class="al-toolbar__count" id="ports-count"></div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.Ports.table')
        </div>
    </div>
</div>
@stop

@section('javascript')
    <script>
        $(document).on('click', '.extra-empty input', function() {
            var parent = $(this).closest('.extra-empty');
            var time = new Date().getTime();
            parent.removeClass('extra-empty');
            parent.after('<div class="row extra-empty"><div class="col-xs-6"><input class="form-control" name="extra[' + time + '][name]" type="text"></div><div class="col-xs-6"><div class="input-group"><input class="form-control" name="extra[' + time + '][value]" type="text"><span class="input-group-addon"><a href="javascript:" class="delete-extra-item remove-icon"><span aria-hidden="true">×</span></a></span></div></div></div>');
        });

        $(document).on('click', 'div.row:not(.extra-empty) .delete-extra-item', function() {
            $(this).closest('.row').remove();
        });

        tables.set_config('table_ports', {
            url:'{{ route("admin.ports.index") }}'
        });

        function ports_edit_modal_callback() {
            tables.get('table_ports');
        }
        function update_config_modal_callback() {
            tables.get('table_ports');
        }

        /* -------- search and status filter, applied to the rendered rows -------- */
        var portsFilter = (function () {
            var $scope = $('#table_ports');

            function rows() {
                return $scope.find('table tbody tr[data-port]');
            }

            // the counts are written straight to the DOM: the chips live outside the
            // table the plugin rewrites, so nothing else can touch them
            function setCount(state, value) {
                var chip = document.querySelector('#table_ports .al-filter[data-state="' + state + '"] .al-filter__count');

                if (chip)
                    chip.textContent = String(value);
            }

            function apply() {
                var query = $.trim($('#ports-search').val() || '').toLowerCase();
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

                $scope.find('.ports-none').toggleClass('is-hidden', shown !== 0 || all.length === 0);

                var counter = $('#ports-count').get(0);

                if (counter)
                    counter.textContent = shown + ' of ' + all.length + ' ports';
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
                    $scope.on('input', '#ports-search', apply);

                    $scope.on('click', '.al-filter', function () {
                        $(this).addClass('is-active').siblings().removeClass('is-active');
                        apply();
                    });

                    // the table plugin swaps the card's contents after an edit or a
                    // config reset, so keep the filter applied to whatever is shown
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
            portsFilter.init();
        });
    </script>
@stop

@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-list-overrides.css') }}">
@stop

@section('content')
<div class="al-page">

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-shield-halved"></i></div>
            <div>
                <h1 class="al-page__title">{!! trans('admin.blocked_ips') !!}</h1>
                <p class="al-page__subtitle">Addresses refused at sign-in. An address is blocked from its next attempt.</p>
            </div>
        </div>

        <a href="javascript:" class="al-add"
           data-modal="blocked_ips_create"
           data-url="{{ route('admin.blocked_ips.create') }}">
            <i class="fas fa-plus"></i> {{ trans('admin.add_new') }}
        </a>
    </div>

    {{-- the toolbar sits outside [data-table] on purpose: the table plugin
         replaces that element's contents on every refresh --}}
    <div class="al-card al-card--scroll" id="table_blocked_ips">

        <div class="al-toolbar">
            <div class="al-search">
                <i class="fas fa-search"></i>
                <input type="text" id="blocked-ips-search" placeholder="Search by IP address..." autocomplete="off">
            </div>

            <div class="al-toolbar__count" id="blocked-ips-count"></div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.BlockedIps.table')
        </div>
    </div>
</div>
@stop

@section('javascript')
    <script>
        tables.set_config('table_blocked_ips', {
            url:'{{ route("admin.blocked_ips.index") }}'
        });

        function blocked_ips_create_modal_callback() {
            tables.get('table_blocked_ips');
        }
        function blocked_ips_destroy_modal_callback() {
            tables.get('table_blocked_ips');
        }

        /* -------- search, applied to the rendered rows -------- */
        var blockedIpsSearch = (function () {
            var $scope = $('#table_blocked_ips');

            function rows() {
                return $scope.find('table tbody tr[data-ip]');
            }

            function apply() {
                var query = $.trim($('#blocked-ips-search').val() || '').toLowerCase();
                var all = rows();
                var shown = 0;

                all.each(function () {
                    var hit = !query || ($(this).attr('data-search') + '').indexOf(query) !== -1;

                    $(this).toggleClass('is-hidden', !hit);

                    if (hit)
                        shown++;
                });

                $scope.find('.blocked-ips-none').toggleClass('is-hidden', shown !== 0 || all.length === 0);

                var counter = $('#blocked-ips-count').get(0);

                if (counter)
                    counter.textContent = shown + ' of ' + all.length + ' addresses';
            }

            return {
                init: function () {
                    $scope.on('input', '#blocked-ips-search', apply);

                    // the table plugin swaps the card's contents after adding or
                    // removing an address, so keep the search applied
                    if (window.MutationObserver) {
                        var target = $scope.find('[data-table]').get(0);

                        if (target) {
                            new MutationObserver(apply).observe(target, {childList: true, subtree: true});
                        }
                    }

                    apply();
                }
            };
        })();

        $(function () {
            blockedIpsSearch.init();
        });
    </script>
@stop

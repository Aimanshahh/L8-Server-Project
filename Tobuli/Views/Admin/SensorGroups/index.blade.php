@extends('Admin.Layouts.default')

@section('content')
<div class="al-page">

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-layer-group"></i></div>
            <div>
                <h1 class="al-page__title">{{ trans('admin.sensor_groups') }}</h1>
                <p class="al-page__subtitle">Group sensors once and reuse the group on every object</p>
            </div>
        </div>

        <a href="javascript:" class="al-add"
           data-modal="sensor_groups_create"
           data-url="{{ route('admin.sensor_groups.create') }}">
            <i class="fas fa-plus"></i> {{ trans('global.add_new') }}
        </a>
    </div>

    {{-- the toolbar sits outside [data-table] on purpose: the table plugin
         replaces that element's contents on every refresh --}}
    <div class="al-card" id="table_sensor_groups">

        <div class="al-toolbar">
            <div class="al-search">
                <i class="fas fa-search"></i>
                <input type="text" id="sensor-groups-search" placeholder="Search by group title..." autocomplete="off">
            </div>

            <div class="al-toolbar__count" id="sensor-groups-count"></div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.SensorGroups.table')
        </div>
    </div>
</div>
@stop

@section('javascript')
<script>
    tables.set_config('table_sensor_groups', {
        url:'{{ route("admin.sensor_groups.index") }}',
        delete_url:'{{ route("admin.sensor_groups.destroy") }}'
    });

    function sensor_groups_edit_modal_callback() {
        tables.get('table_sensor_groups');
    }

    function sensor_groups_create_modal_callback() {
        tables.get('table_sensor_groups');
    }

    function sensors_edit_modal_callback() {
        tables.get('table_sensor_group_sensors');
        tables.get('table_sensor_groups');
    }

    function sensors_create_modal_callback() {
        tables.get('table_sensor_group_sensors');
        tables.get('table_sensor_groups');
    }

    $(document).on('updateSensorGroupsTable', function () {
        tables.get('table_sensor_groups');
    });

    /* -------- search, applied to the rendered rows -------- */
    var sensorGroupsSearch = (function () {
        var $scope = $('#table_sensor_groups');

        function rows() {
            return $scope.find('table tbody tr[data-title]');
        }

        function apply() {
            var query = $.trim($('#sensor-groups-search').val() || '').toLowerCase();
            var all = rows();
            var shown = 0;

            all.each(function () {
                var hit = !query || ($(this).attr('data-title') + '').indexOf(query) !== -1;

                $(this).toggleClass('is-hidden', !hit);

                if (hit)
                    shown++;
            });

            $scope.find('.sensor-groups-none').toggleClass('is-hidden', shown !== 0 || all.length === 0);

            var counter = $('#sensor-groups-count').get(0);

            if (counter)
                counter.textContent = shown + ' of ' + all.length + ' ' + (all.length === 1 ? 'group' : 'groups');
        }

        return {
            init: function () {
                $scope.on('input', '#sensor-groups-search', apply);

                // the table plugin swaps the card's contents after a rename or a
                // delete, so keep the search applied to whatever is shown
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
        sensorGroupsSearch.init();
    });
</script>
@stop

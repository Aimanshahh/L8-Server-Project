@extends('Admin.Layouts.default')

@section('content')
<div class="al-page al-formpage">

    <div class="al-page__header">
        <div class="al-page__title-group">
            <div class="al-page__title-icon"><i class="fas fa-tags"></i></div>
            <div>
                <h1 class="al-page__title">{!! trans('front.plans') !!}</h1>
                <p class="al-page__subtitle">Plans users can activate for their objects</p>
            </div>
        </div>

        <a href="javascript:" class="al-add"
           data-modal="device_plans_create"
           data-url="{{ route('admin.device_plan.create') }}">
            <i class="fas fa-plus"></i> {{ trans('admin.add_new') }}
        </a>
    </div>

    <div class="al-card" id="table_device_plans">

        <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
        <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

        <div class="al-toolbar">
            <div class="al-toolbar__switches">
                <label class="al-switch">
                    {!! Form::checkbox('enable_device_plans', 1, settings('main_settings.enable_device_plans') ?? false) !!}
                    <span class="al-switch__track"></span>
                    <span class="al-switch__label">
                        {!! trans('validation.attributes.active') !!}
                        <span class="al-switch__hint">Plans are offered to users</span>
                    </span>
                </label>

                <label class="al-switch">
                    {!! Form::checkbox('group_device_plans', 1, settings('main_settings.group_device_plans') ?? false) !!}
                    <span class="al-switch__track"></span>
                    <span class="al-switch__label">
                        {!! trans('admin.group_plans_by_duration_type') !!}
                        <span class="al-switch__hint">One group per duration</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="al-toolbar">
            <div class="al-search">
                <i class="fas fa-search"></i>
                {!! Form::text('search_phrase', request('search_phrase'), [
                    'class' => 'form-control',
                    'placeholder' => 'Search by plan title...',
                    'data-filter' => 'true',
                    'autocomplete' => 'off',
                ]) !!}
            </div>

            {{-- the status filter is sent to the server through this hidden field --}}
            <input type="hidden" name="active" value="{{ $active === null ? '' : $active }}" data-filter>

            <div class="al-filters" role="group">
                <button type="button" class="al-filter {{ $active === null ? 'is-active' : '' }}" data-state="">
                    {{ trans('global.all') }} <span class="al-filter__count">{{ $counts['all'] }}</span>
                </button>
                <button type="button" class="al-filter {{ $active === 1 ? 'is-active' : '' }}" data-state="1">
                    {!! trans('validation.attributes.active') !!} <span class="al-filter__count">{{ $counts['on'] }}</span>
                </button>
                <button type="button" class="al-filter {{ $active === 0 ? 'is-active' : '' }}" data-state="0">
                    {!! trans('front.inactive') !!} <span class="al-filter__count">{{ $counts['off'] }}</span>
                </button>
            </div>

            <div class="al-toolbar__count" id="device-plans-count">
                {{ $items->total() }} {{ str_plural('plan', $items->total()) }}
            </div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.DevicePlans.table')
        </div>
    </div>
</div>
@stop

@section('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/bootstrap-wysihtml5/bootstrap-wysihtml5.css') }}"/>
@stop

@section('javascript')
    <script src="{{ asset('assets/plugins/bootstrap-wysihtml5/wysihtml5-0.3.0.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/plugins/bootstrap-wysihtml5/bootstrap-wysihtml5.js') }}" type="text/javascript"></script>
    <script>
        tables.set_config('table_device_plans', {
            url:'{{ route("admin.device_plan.index") }}'
        });

        function device_plans_edit_modal_callback() {
            tables.get('table_device_plans');
        }

        function device_plans_create_modal_callback() {
            tables.get('table_device_plans');
        }

        $(document).on('change', 'input[name="enable_device_plans"]', function() {
            $.ajax({
                type: 'POST',
                url: '{{ route('admin.device_plan.toggle_active') }}',
                beforeSend: function() {
                    loader.add('.panel-body');
                },
                success: function(res) {

                },
                complete: function () {
                    loader.remove('.panel-body');
                }
            });
        });

        $(document).on('change', 'input[name="group_device_plans"]', function() {
            $.ajax({
                type: 'POST',
                url: '{{ route('admin.device_plan.toggle_group') }}',
                beforeSend: function() {
                    loader.add('.panel-body');
                },
                success: function(res) {

                },
                complete: function () {
                    loader.remove('.panel-body');
                }
            });
        });

        /* -------- status filter -------- */
        var $devicePlanScope = $('#table_device_plans');

        $devicePlanScope.on('click', '.al-filter', function () {
            $devicePlanScope.find('.al-filter').removeClass('is-active');
            $(this).addClass('is-active');
            $devicePlanScope.find('input[name="active"]').val($(this).attr('data-state'));

            tables.get('table_device_plans');
        });

        /* -------- keep the toolbar counters in step with the table -------- */
        function devicePlansSyncCounters() {
            var $meta = $devicePlanScope.find('[data-table] .table-meta').first();

            if ( ! $meta.length)
                return;

            var total = parseInt($meta.attr('data-total'), 10) || 0;

            $('#device-plans-count').text(total + ' ' + (total === 1 ? 'plan' : 'plans'));

            $devicePlanScope.find('.al-filter[data-state=""] .al-filter__count').text($meta.attr('data-all'));
            $devicePlanScope.find('.al-filter[data-state="1"] .al-filter__count').text($meta.attr('data-on'));
            $devicePlanScope.find('.al-filter[data-state="0"] .al-filter__count').text($meta.attr('data-off'));
        }

        $(function () {
            devicePlansSyncCounters();

            if (window.MutationObserver) {
                var target = $devicePlanScope.find('[data-table]').get(0);

                if (target) {
                    new MutationObserver(devicePlansSyncCounters).observe(target, {childList: true, subtree: true});
                }
            }
        });
    </script>
@stop

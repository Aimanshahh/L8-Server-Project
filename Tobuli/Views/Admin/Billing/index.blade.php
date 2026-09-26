@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-settings-overrides.css') }}">
@stop

@section('content')
<div class="st-page">

    <div class="st-page__header">
        <div class="st-page__title-group">
            <div class="st-page__title-icon"><i class="fas fa-user-gear"></i></div>
            <div>
                <h1 class="st-page__title">{{ trans('validation.attributes.user') }}</h1>
                <p class="st-page__subtitle">Defaults applied to every new account, what those accounts may do, and your subscription plans.</p>
            </div>
        </div>
    </div>

    @if (Session::has('user_defaults_errors'))
        <div class="alert alert-danger">
            <ul>
                @foreach (Session::get('user_defaults_errors')->all() as $error)
                    <li>{!! $error !!}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="st-grid">

        {{-- ------------------------------------------------ settings column --}}
        <div>
            <div class="panel panel-default">

                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="fas fa-clipboard-list"></i>
                        <span class="st-card__headtext">
                            <span class="st-card__title">New user defaults</span>
                            <span class="st-card__sub">Used for every account created after you save</span>
                        </span>
                        <button type="button" class="st-acc-toggle" id="billing-acc-toggle-all">Expand all</button>
                    </div>
                </div>

                <div class="panel-body">
                    {!! Form::open(array('route' => 'admin.main_server_settings.new_user_defaults_save', 'method' => 'POST', 'class' => 'form form-horizontal', 'id' => 'new-user-defaults-form')) !!}

                    {{-- ------------------------------------------- registration --}}
                    <details class="st-acc" open>
                        <summary class="st-acc__head">
                            <span class="st-acc__chev"><i class="fas fa-chevron-right"></i></span>
                            <span class="st-acc__text">
                                <span class="st-acc__title">{{ trans('front.registration') }}</span>
                                <span class="st-acc__sub">Sign up rules, device limits and the default plan</span>
                            </span>
                        </summary>
                        <div class="st-acc__body">

                            <div class="form-group">
                                {!! Form::label('email_verification', trans('validation.attributes.email_verification'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                                <div class="col-xs-12 col-sm-8">
                                    {!! Form::select('email_verification', ['0' => trans('global.no'), '1' => trans('global.yes')], $settings['email_verification'], ['class' => 'form-control']) !!}
                                </div>
                            </div>

                            <div class="form-group">
                                {!! Form::label('allow_users_registration', trans('validation.attributes.allow_users_registration'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                                <div class="col-xs-12 col-sm-8">
                                    {!! Form::select('allow_users_registration', ['0' => trans('global.no'), '1' => trans('global.yes')], $settings['allow_users_registration'], ['class' => 'form-control']) !!}
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-xs-12 col-sm-4"></div>
                                <div class="col-xs-12 col-sm-8">
                                    <div class="checkbox">
                                        {!! Form::checkbox('enable_plans', 1, settings('main_settings.enable_plans'), ['id' => 'enable_plans']) !!}
                                        {!! Form::label('enable_plans', trans('validation.attributes.enable_plans') ) !!}
                                    </div>
                                </div>
                            </div>

                            <div data-disablable="#enable_plans;show-enable">
                                <div class="form-group">
                                    {!! Form::label(null, trans('validation.attributes.devices_limit'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                                    <div class="col-xs-12 col-sm-8">
                                        <div class="input-group">
                                            <div class="checkbox input-group-btn">
                                                {!! Form::checkbox('enable_devices_limit', 1, !is_null(settings('main_settings.devices_limit')), ['id' => 'enable_devices_limit', 'aria-label' => trans('validation.attributes.devices_limit')]) !!}
                                                {{-- the box alone labels this switch, so the label stays empty (Form::label would print the field name) --}}
                                                <label for="enable_devices_limit"></label>
                                            </div>
                                            {!! Form::text('devices_limit', settings('main_settings.devices_limit'), ['class' => 'form-control']) !!}
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    {!! Form::label(null, trans('validation.attributes.subscription_expiration_after_days'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                                    <div class="col-xs-12 col-sm-8">
                                        <div class="input-group">
                                            <div class="checkbox input-group-btn">
                                                {!! Form::checkbox('enable_subscription_expiration_after_days', 1, !is_null(settings('main_settings.subscription_expiration_after_days')), ['id' => 'enable_subscription_expiration_after_days', 'aria-label' => trans('validation.attributes.subscription_expiration_after_days')]) !!}
                                                <label for="enable_subscription_expiration_after_days"></label>
                                            </div>
                                            {!! Form::text('subscription_expiration_after_days', settings('main_settings.subscription_expiration_after_days'), ['class' => 'form-control']) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div data-disablable="#enable_plans;hide-disable">
                                <div class="form-group">
                                    <div class="col-xs-12 col-sm-4"></div>
                                    <div class="col-xs-12 col-sm-8">
                                        <div class="checkbox">
                                            {!! Form::checkbox('allow_user_change_plan', 1, settings('main_settings.allow_user_change_plan')) !!}
                                            {!! Form::label('allow_user_change_plan', trans('validation.attributes.allow_user_change_plan')) !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group" id="default_billing_plan">
                                    {!! Form::label('default_billing_plan', trans('validation.attributes.default_billing_plan'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                                    <div class="col-xs-12 col-sm-8">
                                        {!! Form::select('default_billing_plan', $items->pluck('title','id')->all(), settings('main_settings.default_billing_plan'), ['class' => 'form-control']) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>

                    {{-- ---------------------------------------------- timezone --}}
                    <details class="st-acc">
                        <summary class="st-acc__head">
                            <span class="st-acc__chev"><i class="fas fa-chevron-right"></i></span>
                            <span class="st-acc__text">
                                <span class="st-acc__title">Timezone &amp; daylight saving</span>
                                <span class="st-acc__sub">The clock new accounts start on, and how their DST is applied</span>
                            </span>
                        </summary>
                        <div class="st-acc__body">

                            <div class="form-group">
                                {!! Form::label('default_timezone', trans('validation.attributes.default_timezone'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                                <div class="col-xs-12 col-sm-8">
                                    {!! Form::select('default_timezone', $timezones, $settings['default_timezone'], ['class' => 'form-control']) !!}
                                </div>
                            </div>

                            <div class="form-group">
                                {!! Form::label(null, trans('validation.attributes.daylight_saving_time'), ['class' => 'col-xs-12 col-sm-4 control-label"']) !!}
                                <div class="col-xs-12 col-sm-8">
                                    {!! Form::label('default_dst_type', trans('validation.attributes.dst_type').':') !!}
                                    {!! Form::select('default_dst_type', $dst_types, $settings['default_dst_type'] ?? null, ['class' => 'form-control']) !!}

                                    <div class="row" data-disablable="#default_dst_type;hide-disable;exact">
                                        <div class="col-xs-6">
                                            {!! Form::label('default_dst_date_from', trans('validation.attributes.date_from').':') !!}
                                            {!! Form::text('default_dst_date_from', $settings['default_dst_date_from'] ?? null, ['class' => 'form-control']) !!}
                                        </div>
                                        <div class="col-xs-6">
                                            {!! Form::label('default_dst_date_to', trans('validation.attributes.date_to').':') !!}
                                            {!! Form::text('default_dst_date_to', $settings['default_dst_date_to'] ?? null, ['class' => 'form-control']) !!}
                                        </div>
                                    </div>
                                    <div data-disablable="#default_dst_type;hide-disable;other">
                                        {!! Form::label('date_from', trans('front.from').':') !!}
                                        <div class="row">
                                            <div class="col-xs-4">
                                                {!! Form::select('default_dst_month_from', $months, $settings['default_dst_month_from'] ?? null, ['class' => 'form-control']) !!}
                                            </div>
                                            <div class="col-xs-2">
                                                {!! Form::select('default_dst_week_pos_from', $week_pos, $settings['default_dst_week_pos_from'] ?? null, ['class' => 'form-control']) !!}
                                            </div>
                                            <div class="col-xs-4">
                                                {!! Form::select('default_dst_week_day_from', $weekdays, $settings['default_dst_week_day_from'] ?? null, ['class' => 'form-control']) !!}
                                            </div>
                                            <div class="col-xs-2">
                                                {!! Form::text('default_dst_time_from', $settings['default_dst_time_from'] ?? null, ['class' => 'form-control', 'placeholder' => trans('front.time')]) !!}
                                            </div>
                                        </div>

                                        {!! Form::label('date_to', trans('front.to').':') !!}
                                        <div class="row">
                                            <div class="col-xs-4">
                                                {!! Form::select('default_dst_month_to', $months, $settings['default_dst_month_to'] ?? null, ['class' => 'form-control']) !!}
                                            </div>
                                            <div class="col-xs-2">
                                                {!! Form::select('default_dst_week_pos_to', $week_pos, $settings['default_dst_week_pos_to'] ?? null, ['class' => 'form-control']) !!}
                                            </div>
                                            <div class="col-xs-4">
                                                {!! Form::select('default_dst_week_day_to', $weekdays, $settings['default_dst_week_day_to'] ?? null, ['class' => 'form-control']) !!}
                                            </div>
                                            <div class="col-xs-2">
                                                {!! Form::text('default_dst_time_to', $settings['default_dst_time_to'] ?? null, ['class' => 'form-control', 'placeholder' => trans('front.time')]) !!}
                                            </div>
                                        </div>
                                    </div>
                                    <div data-disablable="#default_dst_type;hide-disable;automatic">
                                        {!! Form::label('default_dst_country_id', trans('front.country').':') !!}
                                        {!! Form::select('default_dst_country_id', $dst_countries, $settings['default_dst_country_id'] ?? null, ['class' => 'form-control', 'data-live-search' => 'true']) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>

                    {{-- ------------------------------------------- permissions --}}
                    <div data-disablable="#enable_plans;show-enable">
                        <details class="st-acc" id="perm-section">
                            <summary class="st-acc__head">
                                <span class="st-acc__chev"><i class="fas fa-chevron-right"></i></span>
                                <span class="st-acc__text">
                                    <span class="st-acc__title">{{ trans('validation.attributes.permissions') }}</span>
                                    <span class="st-acc__sub">What a newly created account may view, edit and delete</span>
                                </span>
                                <span class="st-count" id="perm-count">0 granted</span>
                            </summary>
                            <div class="st-acc__body">

                                <div class="st-toolbar st-toolbar--plain">
                                    <div class="st-search">
                                        <i class="fas fa-magnifying-glass"></i>
                                        <input type="text" id="perm-search" placeholder="Search permissions..." autocomplete="off">
                                    </div>
                                    <div class="st-toolbar__actions">
                                        <span class="st-hint">Tick a column header to apply it to every shown row.</span>
                                    </div>
                                </div>

                                <div class="st-tablewrap">
                                    <table class="table" id="perm-table">
                                        <thead>
                                        <tr>
                                            <th style="text-align: left">{{ trans('front.permission') }}</th>
                                            <th style="text-align: center">
                                                {{ trans('front.view') }}
                                                <input type="checkbox" class="st-thcheck st-col-all" data-col="view" title="{{ trans('front.view') }}">
                                            </th>
                                            <th style="text-align: center">
                                                {{ trans('global.edit') }}
                                                <input type="checkbox" class="st-thcheck st-col-all" data-col="edit" title="{{ trans('global.edit') }}">
                                            </th>
                                            <th style="text-align: center">
                                                {{ trans('global.delete') }}
                                                <input type="checkbox" class="st-thcheck st-col-all" data-col="remove" title="{{ trans('global.delete') }}">
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($grouped_permissions as $group => $permissions)
                                            @if($group !== 'main')
                                                <tr class="group-head table" data-group="{{ $group }}">
                                                    <th colspan="4">
                                                        <a href="javascript:" data-toggle="collapse" data-target="{{ ".group-$group" }}">
                                                            {{ ucfirst($group) }}
                                                            <i class="fa fa-angle-down"></i>
                                                        </a>
                                                    </th>
                                                </tr>
                                            @endif
                                            @foreach($permissions as $permission => $modes)
                                                <tr class="perm-row {{ "group-$group" }} {{ ($group !== 'main') ? 'collapse' : '' }}">
                                                    <td>
                                                        @if($group !== 'main')
                                                            {{ trans('validation.attributes.' . explode('.', $permission)[1]) }}
                                                        @else
                                                            {{ trans('front.' . $permission) }}
                                                        @endif
                                                    </td>
                                                    <td style="text-align: center">
                                                        <div class="checkbox">
                                                            @if ($modes['view'])
                                                                {!! Form::checkbox("perms[$permission][view]", 1, getMainPermission($permission, 'view'), ['class' => 'perm_checkbox perm_view']) !!}
                                                            @else
                                                                {!! Form::checkbox('', 0, 0, ['disabled' => 'disabled']) !!}
                                                            @endif
                                                            {!! Form::label(null, null) !!}
                                                        </div>
                                                    </td>
                                                    <td style="text-align: center">
                                                        <div class="checkbox">
                                                            @if ($modes['edit'])
                                                                {!! Form::checkbox("perms[$permission][edit]", 1, getMainPermission($permission, 'edit'), ['class' => 'perm_checkbox perm_edit']) !!}
                                                            @else
                                                                {!! Form::checkbox('', 0, 0, ['disabled' => 'disabled']) !!}
                                                            @endif
                                                            {!! Form::label(null, null) !!}
                                                        </div>
                                                    </td>
                                                    <td style="text-align: center">
                                                        <div class="checkbox">
                                                            @if ($modes['remove'])
                                                                {!! Form::checkbox("perms[$permission][remove]", 1, getMainPermission($permission, 'remove'), ['class' => 'perm_checkbox perm_remove']) !!}
                                                            @else
                                                                {!! Form::checkbox('', 0, 0, ['disabled' => 'disabled']) !!}
                                                            @endif
                                                            {!! Form::label(null, null) !!}
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                            <tr class="perm-empty is-hidden">
                                                <td colspan="4">No permission matches that search.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>
                    </div>

                    {!! Form::close() !!}
                </div>

                <div class="panel-footer is-sticky">
                    <button type="submit" class="btn btn-action" onClick="$('#new-user-defaults-form').submit();">{{ trans('global.save') }}</button>
                </div>
            </div>
        </div>

        {{-- ---------------------------------------------------- plans column --}}
        <div>
            <div class="panel panel-default" id="table_billing_plans">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="fas fa-layer-group"></i>
                        <span class="st-card__headtext">
                            <span class="st-card__title">{!! trans('front.plans') !!}</span>
                            <span class="st-card__sub">Plans you can assign to accounts</span>
                        </span>
                        <a href="javascript:" class="st-btn st-btn--primary st-btn--sm"
                           data-modal="billing_plans_create"
                           data-url="{{ route("admin.billing.create") }}">
                            <i class="fas fa-plus"></i> {{ trans('admin.add_new') }}
                        </a>
                    </div>
                </div>

                <div class="panel-body" data-table>
                    @include('Admin.Billing.table')
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('javascript')
<script>
    tables.set_config('table_billing_plans', {
        url:'{{ route("admin.billing.plans") }}',
        delete_url:'{{ route("admin.billing.destroy") }}'
    });

    function billing_plans_edit_modal_callback() {
        tables.get('table_billing_plans');
        updateBillingPlans();
    }

    function billing_plans_create_modal_callback() {
        tables.get('table_billing_plans');
        updateBillingPlans();
    }

    function updateBillingPlans() {
        $.ajax({
            type: 'GET',
            dataType: "html",
            url: '{{ route('admin.billing.billing_plans_form') }}',
            success: function(res){
                $('#default_billing_plan div').html(res);
            }
        });
    }

    $(document).ready(function() {
        $(document).on('change', 'select[name="payment_type"]', function() {
            $("div[class*='payment-']").hide();
            $(".payment-" + $(this).val()).show();
        });
        $('select[name="payment_type"]').trigger('change');

        $(document).on('click', '.multi_delete', function() {
            setTimeout(function() {
                updateBillingPlans();
            }, 2000);
        });

        $('input[name="enable_plans"]').trigger('change');

        checkPerms();

        $(document).ready(function () {
            $('input[name="dst_date_from"]').datetimepicker({
                changeYear: false,
                format: 'mm-dd hh:ii',
                closeOnDateSelect: true
            });
            $('input[name="dst_date_to"]').datetimepicker({
                changeYear: false,
                format: 'mm-dd hh:ii',
                closeOnDateSelect: true
            });
        });

        /* ----- collapsible sections ----- */
        $('#billing-acc-toggle-all').on('click', function () {
            var $btn = $(this);
            var expand = $btn.data('open') != 1;

            $btn.data('open', expand ? 1 : 0).text(expand ? 'Collapse all' : 'Expand all');
            $('#new-user-defaults-form').find('details.st-acc').prop('open', expand);
        });

        /* ----- permissions: counters and per column select all ----- */
        var $permTable = $('#perm-table');

        function shownPerms(col) {
            return $permTable.find('tr.perm-row:visible .perm_' + col + ':not(:disabled)');
        }

        function syncPermCounters() {
            $('#perm-count').text($permTable.find('.perm_checkbox:checked:not(:disabled)').length + ' granted');

            $.each(['view', 'edit', 'remove'], function (i, col) {
                var $boxes = shownPerms(col);
                var $checked = $boxes.filter(':checked');
                var $all = $('.st-col-all[data-col="' + col + '"]');

                $all.prop('checked', $boxes.length > 0 && $checked.length === $boxes.length);
                $all.prop('indeterminate', $checked.length > 0 && $checked.length < $boxes.length);
            });
        }

        $(document).on('change', '.st-col-all', function () {
            var col = $(this).data('col');

            shownPerms(col).prop('checked', $(this).prop('checked')).trigger('change');
            syncPermCounters();
        });

        $(document).on('change', '#perm-table .perm_checkbox', function () {
            syncPermCounters();
        });

        /* ----- permissions: search, expanding groups that match ----- */
        var expandedBeforeSearch = null;

        function rememberExpanded() {
            if (expandedBeforeSearch !== null)
                return;

            expandedBeforeSearch = [];

            $permTable.find('tr.group-head').each(function () {
                var group = $(this).data('group');

                if ($permTable.find('tr.perm-row.group-' + group + '.in').length)
                    expandedBeforeSearch.push(group);
            });
        }

        function setGroupOpen(group, open) {
            var $rows = $permTable.find('tr.perm-row.group-' + group);

            if ($.fn.collapse) {
                $rows.collapse(open ? 'show' : 'hide');
            } else {
                $rows.toggleClass('collapse in', open).css('display', open ? '' : 'none');
            }
        }

        $('#perm-search').on('input', function () {
            var q = $.trim($(this).val()).toLowerCase();
            var matched = 0;

            $permTable.find('tr.perm-row').each(function () {
                var hit = !q || $(this).find('td').first().text().toLowerCase().indexOf(q) !== -1;

                $(this).toggleClass('is-hidden', !hit);

                if (hit)
                    matched++;
            });

            $permTable.find('tr.group-head').each(function () {
                var group = $(this).data('group');
                var any = $permTable.find('tr.perm-row.group-' + group + ':not(.is-hidden)').length > 0;

                $(this).toggleClass('is-hidden', !any);

                if (q) {
                    rememberExpanded();
                    setGroupOpen(group, any);
                } else if (any) {
                    setGroupOpen(group, $.inArray(group, expandedBeforeSearch || []) !== -1);
                }
            });

            if (!q) {
                expandedBeforeSearch = null;
            } else {
                $('.st-col-all').prop('checked', false).prop('indeterminate', false);
            }

            $permTable.find('tr.perm-empty').toggleClass('is-hidden', matched !== 0);
            syncPermCounters();
        });

        syncPermCounters();
    });

    $(document).on('change', 'input.perm_checkbox', function () {
        checkPerm($(this));
    });

    $('input[name^="default_dst_date_"]').datetimepicker({
        changeYear: false,
        format: 'mm-dd hh:ii',
        closeOnDateSelect: true,
        weekStart: app.settings.weekStart
    }).on('monthUpdate', titleRemoveYear);
</script>
@stop

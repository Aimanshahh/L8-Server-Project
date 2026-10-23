@extends('Frontend.Layouts.loged')

<style>
    /* 🚀 PREMIUM UI/UX FOR OBJECTS LIST */
    .device-card {
        background: #ffffff;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 12px 16px !important;
        margin-bottom: 8px;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .device-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.08);
        border-color: #cbd5e1 !important;
    }
    .device-card__name {
        font-weight: 700 !important;
        font-size: 14px !important;
        color: #0f172a !important;
    }
    .device-card__speed {
        font-weight: 600;
        color: #3b82f6;
        font-size: 13px;
        background: rgba(59, 130, 246, 0.1);
        padding: 2px 8px;
        border-radius: 6px;
    }
    .device-card__address, .device-card__time {
        font-size: 12px !important;
        color: #64748b !important;
    }
    .device-card__status-dot {
        width: 12px !important;
        height: 12px !important;
        border-radius: 50%;
        box-shadow: 0 0 0 2px #fff;
    }
    .device-card[data-device-status="moving"] .device-card__status-dot {
        animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .device-card.active, .device-card.selected {
        border-color: #3b82f6 !important;
        background: #eff6ff !important;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }
    /* Style the top stat buttons */
    .btn-group .btn {
        border-radius: 8px !important;
        font-weight: 600;
        border: 1px solid #e2e8f0 !important;
    }
</style>


@section('panel_actions')
    @include('Frontend.Objects.partials.addDevice')
@stop

@section('items')
<div class="tab-content">
    <div class="tab-pane active" id="objects_tab">
        @include('Frontend.Objects.tabs.objects')
    </div>
    @if(Auth::user()->perm('events', 'view'))
        <div class="tab-pane" id="events_tab">
            @include('Frontend.Objects.tabs.events')
        </div>
    @endif
    <div class="tab-pane" id="history_tab">
        @include('Frontend.Objects.tabs.history')
    </div>
    <div class="tab-pane" id="alerts_tab">
        @include('Frontend.Objects.tabs.alerts')
    </div>

    @include('Frontend.Objects.tabs.geofencing')
    @include('Frontend.Objects.tabs.routes')
    @include('Frontend.Objects.tabs.pois')
</div>
@include('Frontend.Objects.partials.deleteObject')
@include('Frontend.Objects.partials.deleteGeofence')
@include('Frontend.Objects.partials.deleteRoute')
@include('Frontend.Objects.partials.deleteAlert')
@include('Frontend.Objects.partials.deletePoi')
@include('Frontend.Objects.tools.showPoint')
@include('Frontend.Objects.tools.showAddress')

@stop


<style>
    /* 🚀 FIX JUMPING DROPDOWN & PREMIUM STYLING */
    .device-card .btn-group.dropleft {
        position: relative !important;
    }
    
    .device-card .dropdown-menu {
        position: absolute !important;
        top: 100% !important;
        right: 0 !important;
        left: auto !important;
        transform: none !important;
        margin-top: 8px !important;
        min-width: 220px !important;
        border-radius: 12px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        padding: 8px !important;
        z-index: 9999 !important;
    }

    .device-card .dropdown-menu li a {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        padding: 10px 14px !important;
        border-radius: 8px !important;
        color: #475569 !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        transition: all 0.2s ease !important;
    }

    .device-card .dropdown-menu li a:hover {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
    }

    .device-card .dropdown-menu li a .icon {
        font-size: 15px !important;
        color: #64748b !important;
        width: 16px;
        text-align: center;
    }
    
    .device-card .dropdown-menu li a:hover .icon {
        color: #3b82f6 !important;
    }
</style>

@section('scripts')

<script>
    function my_account_settings_edit_modal_callback(res) {
        if (res.status == 1)
            window.location.reload();
    }

    function devices_create_modal_callback(res) {
        device_added(res);
    }

    function beacons_create_modal_callback(res) {
        device_added(res);
    }

    function device_added(res) {
        if (res.status == 1) {
            app.notice.success('{{ trans('front.successfully_added_device') }}');
            app.devices.loadData(res.id);
            app.devices.list();
        }
    }

    function devices_edit_modal_callback(res) {
        device_edited(res);
    }

    function beacons_edit_modal_callback(res) {
        device_edited(res);
    }

    function device_edited(res) {
        if (res.status == 1) {
            app.notice.success('{{ trans('front.successfully_updated_device') }}');

            if (typeof res.deleted != 'undefined') {
                app.devices.remove(res.id);

                $('.history-tab-form .devices_list option[value="' + res.id + '"]').selectpicker('refresh');
            }

            app.devices.loadData(res.id);
            app.devices.list();
        }
    }

    function email_confirmation_edit_modal_callback(res) {
        if (res.status == 1) {
            app.notice.success('{{ trans('front.successfully_confirmed_email') }}');
            $('#email_confirmation').hide();
        }
    }

    function my_account_edit_modal_callback(res) {
    if (res.status == 1) {
        app.notice.success('{{ trans('front.successfully_updated_profile') }}');
            if (res.email_changed == 1) {
                 $('#email_confirmation').show();
                 $('#email_confirmation a').trigger('click');
            }
        }
    }

    function email_resend_code_modal_callback(res) {
        if (res.status == 1) {
            app.notice.success('{{ trans('front.activation_email_sent') }}');
        }
    }

    function events_do_destroy_modal_callback(res) {
        if (res.status == 1) {
            app.events.list();
        }
    }

    function objects_delete_modal_callback(res) {
        if (res.status == 1) {
            $('.history-tab-form .devices_list option[value="' + res.id + '"]').selectpicker('refresh');

            $('#devices_edit').modal('hide');
            $('#beacons_edit').modal('hide');

            app.devices.remove(res.id);

            app.devices.list();
        }
    }
</script>
@stop
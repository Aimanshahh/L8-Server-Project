@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-objects-overrides.css') }}">
@stop

@section('content')
    @if (Session::has('messages'))
        <div class="alert alert-success">
            <ul>
                @foreach (Session::get('messages') as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="objects-page">
        <div class="objects-page__header">
            <div class="objects-page__title-group">
                <div class="objects-page__title-icon">
                    <i class="fas fa-satellite-dish"></i>
                </div>
                <div>
                    <h1 class="objects-page__title">Connected Devices</h1>
                    <p class="objects-page__subtitle">View which users have which devices connected</p>
                </div>
            </div>
            <div class="objects-cards">
                <div class="objects-card objects-card--blue">
                    <div class="objects-card__icon"><i class="fas fa-mobile-alt"></i></div>
                    <div>
                        <div class="objects-card__label">Total Devices</div>
                        <div class="objects-card__value">{{ $items->total() }}</div>
                    </div>
                </div>
                <div class="objects-card objects-card--green">
                    <div class="objects-card__icon"><i class="fas fa-circle" style="font-size:12px"></i></div>
                    <div>
                        <div class="objects-card__label">Online</div>
                        <div class="objects-card__value">{{ $onlineCount ?? '-' }}</div>
                    </div>
                </div>
                <div class="objects-card objects-card--gray">
                    <div class="objects-card__icon"><i class="fas fa-circle" style="font-size:12px"></i></div>
                    <div>
                        <div class="objects-card__label">Offline</div>
                        <div class="objects-card__value">{{ ($items->total() - ($onlineCount ?? 0)) }}</div>
                    </div>
                </div>
                <div class="objects-card objects-card--teal">
                    <div class="objects-card__icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="objects-card__label">Active Users</div>
                        <div class="objects-card__value">{{ $activeUserCount ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

                <div class="objects-table-card" id="table_{{ $section }}">
            <div class="objects-search-bar">
            <div class="objects-search-bar__wrap"><i class="fas fa-search"></i><input type="text" placeholder="Search by device name, IMEI or user..." data-filter name="search_phrase"></div>
            <div class="objects-search-bar__right">
                <a href="javascript:location.reload()" class="obj-btn obj-btn--ghost"><i class="fas fa-redo"></i> Reset</a>
                @if(config('addon.devices_bulk_delete') && Auth::User()->isAdmin())
                    <a href="javascript:" class="obj-btn obj-btn--ghost" data-modal="{{ $section }}_bulk_delete" data-url="{{ route('admin.objects.bulk_delete') }}"><i class="fas fa-download"></i> Export</a>
                @endif
            </div>
        </div>
            <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
            <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>
            <div class="panel-body" data-table>
                @include('Admin.'.ucfirst($section).'.table')
            </div>
        </div>
    </div>
@stop

@section('javascript')
<script>
    tables.set_config('table_{{ $section }}', {
        url: '{{ route("admin.{$section}.index") }}',
        do_destroy: {
            url: '{{ route('admin.objects.do_destroy') }}',
            modal: '{{$section}}_delete',
            method: 'GET'
        },
        assign: {
            url: '{{ route('admin.objects.assignForm') }}',
            modal: '{{$section}}_assign',
            method: 'GET'
        },
        assign_sensor: {
            url: '{{ route('admin.objects.assignSensorForm') }}',
            modal: '{{$section}}_assign_sensor',
            method: 'GET'
        },
        set_active: {
            url: '{{ route('admin.objects.set_active', 1) }}',
            method: 'POST'
        },
        set_inactive: {
            url: '{{ route('admin.objects.set_active', 0) }}',
            method: 'POST'
        }
    });

    function {{ $section }}_assign_modal_callback() { tables.get('table_{{ $section }}'); }
    function {{ $section }}_edit_modal_callback() { tables.get('table_{{ $section }}'); }
    function {{ $section }}_create_modal_callback() { tables.get('table_{{ $section }}'); }
    function {{ $section }}_import_modal_callback() { tables.get('table_{{ $section }}'); }
    function {{ $section }}_delete_modal_callback() { tables.get('table_{{ $section }}'); }
    $(document).on('bulk_delete_object', function (e, res) {
        $('#objects_bulk_delete .alert-success').css('display', 'block').html(res.content);
    });
</script>
@stop
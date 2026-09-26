<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            @php
                $multiActions = [];
                if (Auth::User()->perm('devices', 'remove')) {
                    $multiActions = array_merge($multiActions, ['do_destroy' => trans('admin.delete_selected')]);
                }
                if (Auth::User()->perm('devices', 'edit')) {
                    $multiActions = array_merge($multiActions, [
                        'assign' => trans('admin.assign_selected'),
                        'assign_sensor' => trans('admin.assign_sensor'),
                        'set_active' => trans('admin.activate_selected'),
                        'set_inactive' => trans('admin.inactivate_selected')
                    ]);
                }
            @endphp
            @if( $multiActions )
                {!! tableHeaderCheckall($multiActions) !!}
            @endif
            {!! tableHeader('validation.attributes.active') !!}
            {!! tableHeaderSort($items->sorting, 'devices.name', 'validation.attributes.name') !!}
            {!! tableHeaderSort($items->sorting, 'devices.imei', 'validation.attributes.imei') !!}
{!! tableHeader('global.online', 'style="text-align:center;"') !!}
{{-- New Traccar: traccar_devices is on a different connection, cannot sort by it. --}}
{!! tableHeader('admin.last_connection') !!}
@if (Auth::user()->can('view', new \Tobuli\Entities\Device(), 'expiration_date'))
        {!! tableHeaderSort($items->sorting, 'expiration_date', 'validation.attributes.expiration_date') !!}
@endif
{!! tableHeader('validation.attributes.user') !!}
{!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @if (count($collection = $items->getCollection()))
            @foreach ($collection as $item)
                <tr>
                    @if( $multiActions )
                        <td>
                            <div class="checkbox">
                                <input type="checkbox" value="{!! $item->id !!}">
                                <label></label>
                            </div>
                        </td>
                    @endif
                    <td>
                        @if( $item->active )
                            <span class="obj-active-pill">Active</span>
                        @else
                            <span class="obj-active-pill" style="background:#FEF2F2;color:#DC2626;">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="obj-device-cell">
                            <div class="obj-device-icon"><i class="fas fa-mobile-alt"></i></div>
                            <span class="obj-device-name">{{ $item->name }}</span>
                        </div>
                    </td>
                    <td><span class="obj-imei">{{ $item->imei }}</span></td>
                    <td style="text-align: center">
                        @php
                            $status = $item->getStatus();
                            $pillClass = 'obj-status-pill--offline';
                            if ($status === 'online') $pillClass = 'obj-status-pill--online';
                            elseif (in_array($status, ['ack', 'engine'])) $pillClass = 'obj-status-pill--ack';
                        @endphp
                        <span class="obj-status-pill {{ $pillClass }}" data-toggle="tooltip" title="{{ trans('global.' . $status) }}">
                            {{ ucfirst($status) }}
                        </span>
                    </td>
                    <td><div class="obj-last-conn">{{ $item->server_time ? Formatter::time()->human($item->server_time) : trans('front.not_connected') }}</div></td>
                    @if (Auth::user()->can('view', $item, 'expiration_date'))
                        <td><div class="obj-expiration-cell"><i class="far fa-calendar"></i> {{ $item->hasExpireDate() ? Formatter::time()->human($item->expiration_date) : trans('front.unlimited') }}</div></td>
                    @endif
                    @php
                        $userList = $item->users->filter(function($value){ return auth()->user()->can('show', $value); });
                        $firstUser = $userList->first();
                        $userEmails = $userList->implode('email', ', ');
                    @endphp
                    <td>
                        <div class="obj-user-cell" title="{{ $userEmails }}">
                            @if($firstUser)
                                @php $initial = strtoupper(substr($firstUser->email, 0, 1)); $colors = ['#2563EB','#7C3AED','#0D9488','#EA580C','#DC2626','#16A34A','#4F46E5']; $color = $colors[$item->id % count($colors)]; @endphp
                                <span class="obj-avatar" style="background:{{ $color }}">{{ $initial }}</span>
                            @endif
                            <span class="obj-user-email">{{ $userEmails }}</span>
                        </div>
                    </td>
                    <td class="actions">
                        <div class="btn-group dropdown droparrow" data-position="fixed">
                            <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                            <ul class="dropdown-menu">
                                @if( Auth::User()->perm('devices', 'edit') )
                                    <li><a href="javascript:" data-modal="devices_edit" data-url="{{ route("devices.edit", [$item->id, 1]) }}"><i class="fas fa-pen" style="width:16px;color:#64748B;font-size:13px"></i> {{ trans('global.edit') }}</a></li>
                                @endif
                                @if( Auth::User()->perm('devices', 'view') )
                                    <li><a href="{{ route('devices.follow_map', [$item->id]) }}" onClick="dialogWindow(event, '{{$item->name}}');"><i class="fas fa-map-marker-alt" style="width:16px;color:#64748B;font-size:13px"></i> {{ trans('front.follow') }}</a></li>
                                @endif
                                @if(Auth::User()->perm('devices', 'view'))
                                    <li><a href="javascript:" data-modal="device_positions_backups" data-url="{{ route('admin.objects.positions_backups.index', $item->id) }}"><i class="fas fa-history" style="width:16px;color:#64748B;font-size:13px"></i> {{ trans('front.positions_backups') }}</a></li>
                                @endif
                                @if( Auth::User()->perm('devices', 'edit') && $item->app_uuid )
                                    <li><a href="{{ route("devices.do_reset_app_uuid", $item->id) }}"><i class="fas fa-redo" style="width:16px;color:#64748B;font-size:13px"></i> {{ trans('front.reset_app_uuid') }}</a></li>
                                @endif
                                @if( Auth::User()->perm('devices', 'remove') )
                                    <li><a href="javascript:" data-modal="devices_delete" data-url="{{ route('devices.do_destroy', ['id' => $item->id]) }}"><i class="fas fa-trash-alt" style="width:16px;color:#64748B;font-size:13px"></i> {{ trans('global.delete') }}</a></li>
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr><td class="no-data" colspan="8">{!! trans('admin.no_data') !!}</td></tr>
        @endif
        </tbody>
    </table>
</div>

@include('admin::Layouts.partials.pagination', ['limitChoice' => 1])
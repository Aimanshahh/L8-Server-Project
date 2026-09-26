<style>
  .devices-table {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
  }
  .devices-table .table {
    margin-bottom: 0;
  }
  .devices-table .table th {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #64748B;
    background: #F8FAFC;
    padding: 14px 16px;
    border-bottom: 2px solid #E2E8F0;
  }
  .devices-table .table td {
    padding: 14px 16px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
    font-size: 13px;
    color: #334155;
  }
  .devices-table .table tbody tr {
    transition: background .15s;
  }
  .devices-table .table tbody tr:hover td {
    background: #F8FAFC;
  }
  .devices-table .device-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    /* override legacy theme pill (width:10px + text-indent hack) */
    width: auto;
    height: auto;
    border: none;
    text-indent: 0;
    overflow: visible;
    white-space: nowrap;
    line-height: 1.4;
  }
  .devices-table .device-status.online {
    background: #DCFCE7;
    color: #16A34A;
  }
  .devices-table .device-status.offline {
    background: #FEE2E2;
    color: #DC2626;
  }
  .devices-table .device-status::before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
  }
  .devices-table .col-status {
    text-align: center;
    min-width: 120px;
  }
  .devices-table .col-expiration {
    padding-left: 32px;
    min-width: 190px;
  }
  .devices-table .device-name-cell {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .devices-table .device-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #EFF6FF;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2563EB;
    font-size: 16px;
    flex-shrink: 0;
  }
  .devices-table .device-name {
    font-weight: 600;
    color: #1E293B;
  }
  .devices-table .device-imei {
    font-family: 'SF Mono', 'Consolas', monospace;
    color: #475569;
    font-size: 12.5px;
    letter-spacing: .02em;
  }
  .devices-table .expiration-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748B;
  }
  .devices-table .expiration-cell i {
    color: #94A3B8;
    font-size: 14px;
  }
  .devices-table .device-actions .dropdown-menu {
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(23,32,51,.12);
    padding: 6px;
    min-width: 150px;
  }
  .devices-table .device-actions .dropdown-menu li a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    color: #334155;
    transition: background .12s;
  }
  .devices-table .device-actions .dropdown-menu li a:hover {
    background: #EFF6FF;
    color: #2563EB;
  }
  .devices-table .device-actions .dropdown-menu li a > i {
    width: 16px;
    color: #64748B;
    font-size: 13px;
  }
  .devices-table .no-data {
    padding: 48px 16px;
    text-align: center;
    color: #94A3B8;
    font-size: 13px;
  }
</style>
<table class="table table-condensed devices-table">
    <tr>
        {!! tableHeader('validation.attributes.title') !!}
        {!! tableHeader('validation.attributes.imei') !!}
        {!! tableHeader('global.status', 'style="text-align:center"') !!}
        @if (Auth::user()->can('view', new \Tobuli\Entities\Device(), 'expiration_date'))
            {!! tableHeader('validation.attributes.expiration_date') !!}
        @endif
        {!! tableHeader('admin.actions') !!}
    </tr>
    <tbody>
@if (count($items))
    @foreach ($items as $device)
        <tr>
            <td>
                <div class="device-name-cell">
                    <div class="device-icon"><i class="fas fa-mobile-alt"></i></div>
                    <span class="device-name">{{ $device->name }}</span>
                </div>
            </td>
            <td><span class="device-imei">{{ $device->imei }}</span></td>
            <td class="col-status">
                <span class="device-status {{ $device->getStatus() != 'offline' ? 'online' : 'offline' }}"
                      data-toggle="tooltip"
                      title="{{ $device->getStatus() }}">
                    {{ $device->getStatus() != 'offline' ? 'Online' : 'Offline' }}
                </span>
            </td>
            @if (Auth::user()->can('view', $device, 'expiration_date'))
                <td class="col-expiration">
                    <div class="expiration-cell">
                        <i class="far fa-calendar"></i>
                        {!! $device->hasExpireDate() ? Formatter::time()->human($device->expiration_date) : '<span style="color:#64748B;">' . trans('front.unlimited') . '</span>' !!}
                    </div>
                </td>
            @endif
            <td class="device-actions">
                <div class="btn-group dropdown" data-position="fixed">
                    <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                    <ul class="dropdown-menu">
                        <li><a href="javascript:" data-modal="devices_edit" data-url="{{ route("devices.edit", [$device->id, 1]) }}"><i class="fas fa-pen"></i> {{ trans('global.edit') }}</a></li>
                        <li><a href="{{ route('objects.destroy') }}" class="js-confirm-link" data-confirm="{!! trans('front.do_object_delete') !!}" data-id="{{ $device->id }}" data-method="DELETE"><i class="fas fa-trash-alt"></i> {{ trans('global.delete') }}</a></li>
                    </ul>
                </div>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td class="no-data" colspan="5">
            {{ trans('admin.no_data') }}
        </td>
    </tr>
@endif
    </tbody>
</table>

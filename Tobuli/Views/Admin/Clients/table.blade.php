<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall([
                'do_destroy' => trans('admin.delete_selected'),
                'set_active' => trans('admin.activate_selected'),
                'set_inactive' => trans('admin.inactivate_selected'),
            ]) !!}
            {!! tableHeaderSort($items->sorting, 'active', NULL) !!}
            {!! tableHeaderSort($items->sorting, 'email') !!}
            @if (Auth::User()->isAdmin())
                {!! tableHeaderSort($items->sorting, 'group_id') !!}
                {!! tableHeaderSort($items->sorting, 'manager_id', trans('validation.attributes.manager_id')) !!}
            @endif
            {!! tableHeaderSort($items->sorting, 'devices_count', trans('front.devices')) !!}
            @if (Auth::User()->isAdmin())
                {!! tableHeaderSort($items->sorting, 'subusers_count', trans('admin.subusers')) !!}
            @endif
            {!! tableHeaderSort($items->sorting, 'devices_limit') !!}
            {!! tableHeaderSort($items->sorting, 'subscription_expiration', trans('validation.attributes.expiration_date')) !!}
            {!! tableHeaderSort($items->sorting, 'loged_at') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>

        <tbody>
        @if (count($collection = $items->getCollection()))
            @foreach ($collection as $item)
                @php
                    $limit       = $item->devices_limit;
                    $unlimited   = is_null($limit);
                    $devicesUsed = (int) $item->devices_count;
                    $percent     = $unlimited ? 0 : min(100, round($devicesUsed / max(1, (int) $limit) * 100));
                    $avatarHue   = crc32($item->email) % 360;
                @endphp
                <tr>
                    <td class="users-cell-checkbox">
                        <div class="checkbox">
                            <input type="checkbox" value="{!! $item->id !!}">
                            <label></label>
                        </div>
                    </td>
                    <td class="users-cell-status">
                        @if ($item->active)
                            <span class="users-status-pill users-status-pill--success"><i class="fa fa-circle"></i> {{ trans('validation.attributes.active') }}</span>
                        @else
                            <span class="users-status-pill users-status-pill--muted"><i class="fa fa-circle"></i> {{ trans('front.inactive') }}</span>
                        @endif
                    </td>
                    <td class="users-cell-user">
                        <span class="users-avatar" style="background: hsl({{ $avatarHue }}, 62%, 52%);">{{ mb_strtoupper(mb_substr($item->email, 0, 1)) }}</span>
                        <span class="users-email">{{ $item->email }}</span>
                    </td>
                    @if (Auth::User()->isAdmin())
                        <td class="users-cell-group">
                            <span class="users-role">{{ trans('admin.group_'.$item->group_id) }}</span>
                        </td>
                        <td class="users-cell-manager">
                            @if (!empty($item->manager->email))
                                <span class="users-manager">{{ $item->manager->email }}</span>
                            @else
                                <span class="users-muted">—</span>
                            @endif
                        </td>
                    @endif
                    <td class="users-cell-devices">
                        <div class="users-device-usage">
                            <span class="users-device-usage__text">
                                {{ $devicesUsed }} / {!! $unlimited ? '&infin;' : $limit !!}
                            </span>
                            @if (!$unlimited)
                                <span class="users-device-usage__bar">
                                    <span style="width: {{ $percent }}%;"></span>
                                </span>
                            @else
                                <span class="users-device-usage__bar users-device-usage__bar--unlimited"></span>
                            @endif
                        </div>
                    </td>
                    @if (Auth::User()->isAdmin())
                        <td class="users-cell-subusers">{{ $item->subusers_count }}</td>
                    @endif
                    <td class="users-cell-limit">
                        @if ($unlimited)
                            <span class="users-limit users-limit--unlimited"><i class="fa fa-infinity"></i> {{ trans('front.unlimited') }}</span>
                        @else
                            <span class="users-limit">{{ $limit }}</span>
                        @endif
                        @if (!empty($item->billing_plan))
                            <span class="users-plan">{{ $item->billing_plan->title }}</span>
                        @endif
                    </td>
                    <td class="users-cell-date">
                        {!! $item->hasExpiration()
                                ? Formatter::time()->human($item->subscription_expiration)
                                : '<span class="users-limit users-limit--unlimited"><i class="fa fa-infinity"></i> '.trans('front.unlimited').'</span>'
                        !!}
                    </td>
                    <td class="users-cell-date">
                        {!! Formatter::time()->human($item->loged_at) ?: '<span class="users-muted">—</span>' !!}
                    </td>
                    <td class="users-cell-actions">
                        @php
                            $canEdit = auth()->user()->can('edit', $item);
                            $canLogin = auth()->user()->can('login_as', $item);
                            $canRemove = auth()->user()->can('remove', $item);
                        @endphp

                        @if($canEdit || $canLogin || $canRemove)
                            <div class="btn-group dropdown droparrow users-actions" data-position="fixed">
                                <button type="button" class="users-actions__btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" title="{{ trans('admin.actions') }}">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu users-actions__menu">
                                    @if($canEdit)
                                        <li>
                                            <a href="javascript:" data-modal="{!! $section !!}_edit" data-url="{!! route("admin.{$section}.edit", $item->id) !!}">
                                                <i class="fa fa-pen"></i> {!! trans('global.edit') !!}
                                            </a>
                                        </li>
                                    @endif

                                    @if($canLogin)
                                        <li>
                                            <a href="javascript:" data-modal="{!! $section !!}_login_as" data-url="{!! route("admin.{$section}.login_as", $item->id) !!}">
                                                <i class="fa fa-sign-in-alt"></i> {!! trans('front.login_as') !!}
                                            </a>
                                        </li>
                                    @endif

                                    @if($canRemove)
                                        <li>
                                            <a href="{!! route("admin.{$section}.destroy", $item->id) !!}"
                                               class="js-confirm-link users-actions__danger"
                                               data-confirm="{!! trans('front.do_delete') !!}"
                                               data-id="{!! $item->id !!}"
                                               data-method="DELETE">
                                                <i class="fa fa-trash"></i> {!! trans('global.delete') !!}
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                        <button type="button" class="users-actions__btn users-actions__btn--devices"
                                data-url="{{ route('admin.clients.get_devices', $item->id) }}"
                                data-toggle="collapse"
                                data-target="#user-devices-{{ $item->id }}"
                                title="{{ trans('front.devices') }}">
                            <i class="fa fa-chevron-down"></i>
                        </button>
                    </td>
                </tr>
                <tr class="row-table-inner">
                    <td colspan="13" id="user-devices-{{ $item->id }}" aria-expanded="false" class="collapse"></td>
                </tr>
            @endforeach
        @else
            <tr class="">
                <td class="no-data" colspan="13">
                    {!! trans('admin.no_data') !!}
                </td>
            </tr>
        @endif
        </tbody>
    </table>
</div>

<div class="nav-pagination users-pagination">
    <div class="users-pagination__size">
        <span class="users-pagination__label">{{ trans('admin.show') }}</span>
        <select class="form-control users-pagination__select" name="limit" data-filter>
            @foreach($limitOptions ?? [10, 25, 50, 100, 500, 1000] as $option)
                <option value="{{ $option }}" @if($items->perPage() == $option) selected @endif>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        <span class="users-pagination__label">{{ trans('admin.entries') }}</span>
    </div>

    <div class="users-pagination__pages">
        {!! $items->render() !!}
    </div>
</div>
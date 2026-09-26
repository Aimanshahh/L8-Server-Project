<div class="table_error"></div>

<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall(['delete_url' => trans('admin.delete_selected')]) !!}
            <th style="width: 1px;">#</th>
            {!! tableHeader('validation.attributes.title') !!}
            {!! tableHeader('admin.sensors_count', 'style="width: 1px;"') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @forelse ($items as $item)
            <tr data-title="{{ strtolower($item->title) }}">
                <td>
                    <div class="checkbox">
                        <input type="checkbox" class="checkboxes" value="{!! $item->id !!}">
                        <label></label>
                    </div>
                </td>
                <td class="al-muted">{{ $item->id }}</td>
                <td>{{ $item->title }}</td>
                <td>
                    <span class="al-chip al-chip--neutral">
                        {{ $item->count }} {{ str_plural(trans('front.sensor'), $item->count) }}
                    </span>
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li><a href="javascript:" data-modal="sensor_groups_edit" data-url="{{ route('admin.sensor_groups.edit', [$item->id]) }}">{!! trans('global.rename') !!}</a></li>
                            <li><a href="javascript:" data-modal="sensor_groups_show" data-url="{{ route('admin.sensor_group_sensors.index', [$item->id, '0']) }}">{!! trans('global.edit') !!}</a></li>
                            <li><a href="{{ route('admin.sensor_groups.destroy', $item->id) }}" class="js-confirm-link al-danger" data-confirm="{!! trans('front.do_delete') !!}" data-id="{{ $item->id }}" data-method="DELETE">{{ trans('global.delete') }}</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="no-data" colspan="5">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-layer-group"></i></div>
                        <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                    </div>
                </td>
            </tr>
        @endforelse

        <tr class="sensor-groups-none is-hidden">
            <td class="no-data" colspan="5">
                <div class="al-empty al-empty--inline">
                    <div class="al-empty__icon"><i class="fas fa-magnifying-glass"></i></div>
                    <p class="al-empty__text">No sensor group matches your search.</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>

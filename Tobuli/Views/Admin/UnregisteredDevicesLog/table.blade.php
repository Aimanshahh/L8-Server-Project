<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall(['delete_url' => trans('admin.delete_selected')]) !!}
            {!! tableHeader('validation.attributes.imei') !!}
            {!! tableHeader('validation.attributes.port') !!}
            <th>IP</th>
            {!! tableHeader('global.date') !!}
            {!! tableHeader('admin.tried_to_connect') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @forelse ($items as $item)
            <tr>
                <td>
                    <div class="checkbox">
                        <input type="checkbox" value="{!! $item->imei !!}">
                        <label></label>
                    </div>
                </td>
                <td><span class="al-code">{{ $item->imei }}</span></td>
                <td><span class="al-chip al-chip--neutral">{{ $item->port }}</span></td>
                <td><span class="al-muted">{{ $item->ip ?: '—' }}</span></td>
                <td>{{ $item->date ? Formatter::time()->human($item->date) : '—' }}</td>
                <td><span class="lg-count">{{ number_format($item->times) }}</span></td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{{ route('admin.unregistered_devices_log.destroy') }}"
                                   class="js-confirm-link al-danger"
                                   data-confirm="{{ trans('admin.do_delete') }}"
                                   data-id="{{ $item->imei }}"
                                   data-method="DELETE">
                                    <i class="fas fa-trash-alt"></i> {{ trans('global.delete') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="no-data" colspan="7">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-user-secret"></i></div>
                        <p class="al-empty__text">{{ trans('admin.no_data') }}</p>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@include("Admin.Layouts.partials.pagination", ['items' => $items])

<table class="table table-list">
    <thead>
    <tr>
        {!! tableHeaderSort($items->sorting, 'name') !!}
        {!! tableHeaderSort($items->sorting, 'launcher') !!}
        {!! tableHeaderSort($items->sorting, 'created_at') !!}
        {!! tableHeader('front.progress') !!}
        {!! tableHeader('front.completed') !!}
        {!! tableHeader('validation.attributes.message') !!}
        {!! tableHeader('admin.actions', 'style="text-align:right"') !!}
    </tr>
    </thead>
    <tbody>
    @php /** @var \Tobuli\Entities\Backup $item */ @endphp
    @forelse ($items->getCollection() as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td>
                <a class="al-ghost" href="javascript:" data-url="{{ route('admin.backups.show', $item->id) }}">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </td>
            <td class="al-muted">{{ Formatter::time()->human($item->created_at) }}</td>
            <td>
                @php
                    $done = $item->progressDone();
                    $total = $item->progressTotal();
                    $percentage = $total ? (int)($done / $total * 100) : 100;
                @endphp

                <div class="al-progress">
                    <div class="al-progress__bar"
                         role="progressbar"
                         aria-valuenow="{{ $done }}"
                         aria-valuemin="0"
                         aria-valuemax="{{ $total }}"
                         style="width: {{ $percentage }}%;">
                        {{ $done }} / {{ $total }}
                    </div>
                </div>
            </td>
            <td>
                <span class="al-pill {{ $item->isCompleted() ? 'al-pill--on' : 'al-pill--off' }}">
                    {{ $item->isCompleted() ? trans('global.yes') : trans('global.no') }}
                </span>
            </td>
            <td class="al-muted al-message">{{ $item->message ?: trans('admin.no_data') }}</td>
            <td class="actions">
                <div class="dropdown">
                    <button type="button" class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ trans('admin.view_details') }}">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="javascript:" data-url="{{ route('admin.backup.processes', $item->id) }}" data-toggle="collapse" data-target="#backup-processes-{{ $item->id }}">
                                <i class="fas fa-history" style="width:16px;color:#64748B;font-size:13px;margin-right:8px"></i>
                                {{ trans('admin.view_details') }}
                            </a>
                        </li>
                        <li>
                            <a class="al-danger" href="javascript:" data-url="{{ route('admin.backups.delete', $item->id) }}" data-method="DELETE" data-confirm="{{ trans('admin.delete_confirm') }}">
                                <i class="fas fa-trash-alt" style="width:16px;color:#DC2626;font-size:13px;margin-right:8px"></i>
                                {{ trans('admin.delete') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </td>
        </tr>
        <tr class="row-table-inner">
            <td colspan="7" id="backup-processes-{{ $item->id }}" aria-expanded="false" class="collapse"></td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="no-data">
                {{ trans('admin.no_data') }}
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

@include('Admin.Layouts.partials.pagination')

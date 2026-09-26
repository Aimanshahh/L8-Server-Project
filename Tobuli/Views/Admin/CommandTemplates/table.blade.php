<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall(['delete_url' => trans('admin.delete_selected')]) !!}
            {!! tableHeaderSort($items->sorting, 'type') !!}
            {!! tableHeaderSort($items->sorting, 'title') !!}
            {!! tableHeaderSort($items->sorting, 'protocol') !!}
            {!! tableHeaderSort($items->sorting, 'adapted') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @forelse ($items->getCollection() as $item)
            <tr>
                <td>
                    <div class="checkbox">
                        <input type="checkbox" value="{!! $item->id !!}">
                        <label></label>
                    </div>
                </td>
                <td>
                    <span class="al-chip {{ $item->type == \Tobuli\Entities\UserSmsTemplate::TYPE ? 'al-chip--violet' : '' }}">
                        {{ $types[$item->type] ?? $item->type }}
                    </span>
                </td>
                <td>{{ $item->title }}</td>
                <td>
                    @if (!empty($item->protocol))
                        <span class="al-code">{{ $protocols[$item->protocol] ?? $item->protocol }}</span>
                    @endif
                </td>
                <td>
                    <span class="al-chip al-chip--neutral">{{ $adapties[$item->adapted] ?? $item->adapted }}</span>
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="javascript:" data-modal="command_templates_edit" data-url="{{ route('admin.command_templates.edit', $item->id) }}">
                                    {{ trans('global.edit') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.command_templates.destroy', ['id' => $item->id]) }}"
                                   class="js-confirm-link al-danger"
                                   data-confirm="{{ trans('admin.do_delete') }}"
                                   data-id="{{ $item->id }}"
                                   data-method="DELETE">
                                    {{ trans('global.delete') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="no-data" colspan="6">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-terminal"></i></div>
                        <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@include("Admin.Layouts.partials.pagination")

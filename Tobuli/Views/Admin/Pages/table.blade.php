<div class="table_error"></div>

<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall(['delete_url' => trans('admin.delete_selected')]) !!}
            {!! tableHeaderSort($items->sorting, 'title') !!}
            {!! tableHeaderSort($items->sorting, 'slug') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @php /** @var \Tobuli\Entities\Page $item */ @endphp
        @forelse ($items->getCollection() as $item)
            <tr>
                <td>
                    <div class="checkbox">
                        <input type="checkbox" value="{!! $item->id !!}">
                        <label></label>
                    </div>
                </td>
                <td>{{ $item->title }}</td>
                <td><span class="al-code">{{ $item->slug }}</span></td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{{ route('pages.show', $item->slug) }}" target="_blank">
                                    {{ trans('front.preview') }}
                                </a>
                            </li>
                            <li>
                                <a href="javascript:" data-modal="pages_edit" data-url="{{ route('admin.pages.edit', $item->id) }}">
                                    {{ trans('global.edit') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.pages.destroy', ['id' => $item->id]) }}"
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
                <td class="no-data" colspan="4">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-file-lines"></i></div>
                        <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@include("Admin.Layouts.partials.pagination")

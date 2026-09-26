<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall(['delete_url' => trans('admin.delete_selected')]) !!}
            {!! tableHeader('validation.attributes.name') !!}
            {!! tableHeader('global.date') !!}
            {!! tableHeader('admin.size') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @forelse ($items as $item)
            <tr>
                <td>
                    <div class="checkbox">
                        <input type="checkbox" value="{!! $item->name !!}">
                        <label></label>
                    </div>
                </td>
                <td>
                    <div class="lg-file">
                        <span class="lg-file__icon"><i class="fas fa-file-lines"></i></span>
                        <span class="lg-file__name">{{ $item->basename }}</span>
                    </div>
                </td>
                <td>{{ date('Y-m-d', strtotime($item->created_at)) }}</td>
                <td><span class="al-chip al-chip--neutral">{{ $item->size }}</span></td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true"
                           aria-expanded="true">
                        </i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{{ route('admin.logs.download', [$item->name]) }}">
                                    <i class="fas fa-download"></i> {{ trans('admin.download') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.logs.delete') }}"
                                   class="js-confirm-link al-danger"
                                   data-confirm="{{ trans('admin.do_delete') }}"
                                   data-id="{{ $item->name }}"
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
                <td class="no-data" colspan="5">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-file-lines"></i></div>
                        <p class="al-empty__text">{{ trans('admin.no_data') }}</p>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="nav-pagination">
    {!! $items->render() !!}
</div>

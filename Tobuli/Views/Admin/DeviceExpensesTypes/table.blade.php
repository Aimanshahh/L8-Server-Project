<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall(['delete_url' => trans('admin.delete_selected')]) !!}
            {!! tableHeader('validation.attributes.name') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>

        <tbody>
        @if ( ! $types->isEmpty())
            @foreach ($types as $type)
                <tr>
                    <td>
                        <div class="checkbox">
                            <input type="checkbox" value="{!! $type->id !!}">
                            <label></label>
                        </div>
                    </td>
                    <td>{{ $type->name }}</td>
                    <td class="actions">
                        <div class="btn-group dropdown droparrow" data-position="fixed">
                            <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true"
                               aria-expanded="true"></i>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="javascript:"
                                       data-modal="device_expenses_types_edit"
                                       data-url="{!! route("admin.device_expenses_types.edit", $type->id) !!}">
                                        {!! trans('global.edit') !!}
                                    </a>
                                </li>
                                <li>
                                    <a href="{!! route("admin.device_expenses_types.destroy", ['id' => $type->id]) !!}"
                                       class="js-confirm-link al-danger"
                                       data-confirm="{!! trans('admin.do_delete') !!}"
                                       data-id="{!! $type->id !!}"
                                       data-method="DELETE">
                                        {!! trans('global.delete') !!}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="no-data" colspan="3">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-receipt"></i></div>
                        <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                    </div>
                </td>
            </tr>
        @endif
        </tbody>
    </table>
</div>

@if ( ! $types->isEmpty())
    <div class="nav-pagination">
        {!! $types->setPath(route('admin.device_expenses_types.index'))->render() !!}
    </div>
@endif

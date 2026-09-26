<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall(['delete_url' => trans('admin.delete_selected')]) !!}
            {!! tableHeaderSort($items->sorting, 'protocol', trans('validation.attributes.device_protocol')) !!}
            {!! tableHeader('front.tags') !!}
            {!! tableHeaderSort($items->sorting, 'message') !!}
            {!! tableHeaderSort($items->sorting, 'always', trans('admin.show_always')) !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @if (count($collection = $items->getCollection()))
            @foreach ($collection as $item)
                <tr>
                    <td>
                        <div class="checkbox">
                            <input type="checkbox" value="{!! $item->id !!}" {{ $item->trial ? 'disabled="disabled"' : '' }}>
                            <label></label>
                        </div>
                    </td>
                    <td>
                        <span class="al-code">{!! $item->port->display ?? '' !!}</span>
                    </td>
                    <td>
                        {!! !empty($item->tags) ? implode(', ', \Illuminate\Support\Arr::pluck($item->tags->toArray(), 'tag')) : '<span class="al-muted">—</span>' !!}
                    </td>
                    <td>
                        {!! $item->message !!}
                    </td>
                    <td>
                        <span class="al-pill {{ $item->always ? 'al-pill--on' : 'al-pill--off' }}">
                            {{ trans('admin.' . ($item->always ? 'yes' : 'no')) }}
                        </span>
                    </td>
                    <td class="actions">
                        <div class="btn-group dropdown droparrow" data-position="fixed">
                            <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                            <ul class="dropdown-menu">
                                <li><a href="javascript:" data-modal="{{ $section }}_edit" data-url="{{ route("admin.{$section}.edit", $item->id) }}">{{ trans('global.edit') }}</a></li>
                                @if (Auth::user()->can('remove', $item))
                                    <li>
                                        <a href="{!! route("admin.{$section}.destroy") !!}"
                                           class="js-confirm-link al-danger"
                                           data-confirm="{!! trans('front.do_delete') !!}"
                                           data-id="{!! $item->id !!}"
                                           data-method="DELETE">{!! trans('global.delete') !!}</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="no-data" colspan="6">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-bell"></i></div>
                        <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                    </div>
                </td>
            </tr>
        @endif
        </tbody>
    </table>
</div>

@include("Admin.Layouts.partials.pagination")

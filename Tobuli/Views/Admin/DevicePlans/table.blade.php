<div class="table_error"></div>

{{-- read back by the toolbar counters after every table refresh --}}
<span class="table-meta" hidden
      data-total="{{ $items->total() }}"
      data-all="{{ $counts['all'] }}"
      data-on="{{ $counts['on'] }}"
      data-off="{{ $counts['off'] }}"></span>

<div class="table-responsive">
    <table class="table table-list">
        <thead>
        <tr>
            {!! tableHeaderSort($items->sorting, 'title') !!}
            {!! tableHeaderSort($items->sorting, 'price') !!}
            {!! tableHeaderSort($items->sorting, 'duration_value', 'validation.attributes.duration_value') !!}
            {!! tableHeader('validation.attributes.active') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>

        <tbody>
        @forelse ($items as $item)
            <tr>
                <td>{!! $item->title !!}</td>
                <td>{!! $item->price !!}</td>
                <td class="al-muted">{!! $item->duration_text !!}</td>
                <td>
                    <span class="al-pill {{ $item->active ? 'al-pill--on' : 'al-pill--off' }}">
                        {!! trans('admin.'.($item->active ? 'yes' : 'no')) !!}
                    </span>
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="javascript:"
                                   data-modal="device_plans_edit"
                                   data-url="{{ route('admin.device_plan.edit', $item->id) }}">
                                    {!! trans('global.edit') !!}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.device_plan.destroy') }}"
                                   class="js-confirm-link al-danger"
                                   data-confirm="{!! trans('front.do_delete') !!}"
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
                <td class="no-data" colspan="5">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-tags"></i></div>
                        <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@include("Admin.Layouts.partials.pagination")

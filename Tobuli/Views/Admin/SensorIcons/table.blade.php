@if (count($items))
    <div class="table-icon">
        @foreach ($items as $item)
            <div class="item">
                <div class="controls">
                    <a href="javascript:" class="sic-act"
                       title="{!! trans('global.edit') !!}"
                       data-modal="sensor_icons_edit"
                       data-url="{!! route('admin.sensor_icons.edit', $item->id) !!}">
                        <i class="fas fa-pen"></i>
                    </a>
                    <a href="javascript:" class="sic-act sic-act--del"
                       title="{!! trans('global.delete') !!}"
                       data-id="{{ $item->id }}">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>

                <div class="sic-thumb">
                    <img src="{{ asset($item->path) }}" alt="">
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="sic-empty">{{ trans('admin.no_data') }}</div>
@endif

@include("admin::Layouts.partials.pagination")

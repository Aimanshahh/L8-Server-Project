@if (count($items))
    <div class="table-icon">
        @foreach ($items as $item)
            <div class="item">
                <div class="controls">
                    <a href="javascript:" class="mic-del"
                       title="{!! trans('global.delete') !!}"
                       data-id="{{ $item->id }}">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>

                <div class="mic-thumb">
                    <img src="{{ asset($item->path) }}" alt="">
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="mic-empty">{{ trans('admin.no_data') }}</div>
@endif

@include("Admin.Layouts.partials.pagination")
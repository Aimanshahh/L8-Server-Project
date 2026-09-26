@if (count($items))
    <div class="table-icon">
        @foreach ($items as $item)
            <div class="item">
                <div class="controls">
                    <a href="javascript:" class="ec-act"
                       title="{!! trans('global.edit') !!}"
                       data-modal="{!! $section !!}_edit"
                       data-url="{!! route("admin.{$section}.edit", $item->id) !!}">
                        <i class="fas fa-pen"></i>
                    </a>
                    <a href="javascript:" class="ec-act ec-act--del"
                       title="{!! trans('global.delete') !!}"
                       data-id="{{ $item->id }}">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>

                <div class="ec-thumb">
                    <img src="{{ asset($item->path) }}" alt="">
                </div>

                <span class="ec-type ec-type--{{ $item->type }}">{{ $item->type }}</span>
            </div>
        @endforeach
    </div>
@else
    <div class="ec-empty">{{ trans('admin.no_data') }}</div>
@endif

@include("Admin.Layouts.partials.pagination")

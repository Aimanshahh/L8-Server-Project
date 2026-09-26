@forelse ($groups as $group)
    <div class="group" data-id="{{ $group['id'] }}">
        <div class="group-body">
            @if(($group['open']))
                @include('front::Objects.items', ['items' => $group['items']])
            @else
                <div data-toggle="scroll" data-parent=".tab-pane-body" data-url="{{ $group['next'] }}"></div>
            @endif
        </div>
    </div>
@empty
    <p class="no-results">{!! trans('front.no_devices') !!}</p>
@endforelse

@if ($groups->nextPageUrl())
    <div data-toggle="scroll" data-parent=".tab-pane-body" data-url="{{ $groups->nextPageUrl() }}"></div>
@endif
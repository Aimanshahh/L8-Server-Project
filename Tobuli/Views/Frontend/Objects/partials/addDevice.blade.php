@if (Auth::User()->perm('custom_device_add', 'view'))
    <a class="op-btn op-btn--primary" href="{!!route('register.step.create', 'device')!!}">
        <i class="icon add"></i>
        <span>{{ trans('front.add_device') }}</span>
    </a>
@else
    @php
        $actions = [];
        if (Auth::User()->perm('devices', 'edit')) {
            $actions[] = [
               'url' => route('devices.create'),
               'modal' => 'devices_create',
               'title' => trans('front.devices'),
            ];
        }
        if (settings('plugins.beacons.status') && Auth::User()->perm('beacons', 'edit')) {
            $actions[] = [
               'url' => route('beacons.create'),
               'modal' => 'beacons_create',
               'title' => trans('front.beacons'),
            ];
        }
    @endphp

    <div class="btn-group" id="device_add_btn">
        @if (count($actions) > 1)
            <button class="btn btn-primary"
                    type="button"
                    data-url="{{ $actions[0]['url'] }}"
                    data-modal="{{ $actions[0]['modal'] }}">
                <i class="icon add"></i>
                <span>{{ trans('front.add_device') }}</span>
            </button>
            <button class="btn btn-primary dropdown-toggle"
                    type="button"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false">
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu pull-right">
                @foreach($actions as $action)
                <li>
                    <a href="javascript:" data-url="{{ $action['url'] }}" data-modal="{{ $action['modal'] }}">
                        {{ $action['title'] }}
                    </a>
                </li>
                @endforeach
            </ul>
        @elseif(count($actions) > 0)
            <button class="btn btn-primary"
                    type="button"
                    data-url="{{ $actions[0]['url'] }}"
                    data-modal="{{ $actions[0]['modal'] }}">
                <i class="icon add"></i>
                <span>{{ trans('front.add_device') }}</span>
            </button>
        @endif
    </div>
@endif

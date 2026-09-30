@if (!empty($events))
    @foreach ($events as $item)
        <tr class="events-row" data-event-id="{!!$item->id!!}" onClick="app.events.select({!!$item->id!!});">
            <td class="events-cell events-cell--time">
                <span class="datetime">
                    <span class="time">{{ Formatter::date()->human($item->time) }}</span>
                    <span class="date">{{ Formatter::dtime()->human($item->time) }}</span>
                </span>
            </td>
            <td class="events-cell events-cell--object">
                <span class="device-name">{{ $item->device->name ?? '' }}</span>
            </td>
            <td class="events-cell events-cell--event">
                <span class="event-title">{{ $item->title }}</span>
                @if (settings('plugins.event_section_address.status'))
                    <span class="event-address">
                        <span data-device="address" data-lat="{{ $item->latitude }}" data-lng="{{ $item->longitude }}"></span>
                    </span>
                @endif
            </td>
            <td class="events-cell events-cell--actions">
                @if(Auth::user()->can('remove', $item))
                <div class="btn-group dropleft droparrow"  data-position="fixed">
                    <i class="btn icon options" data-toggle="dropdown" data-position="fixed" aria-haspopup="true" aria-expanded="false"></i>
                    <ul class="dropdown-menu">

                        @if (!empty($item->alert_id))
                        <li>
                            <a href="javascript:;" data-url="{{ route('alerts.edit', $item->alert_id) }}" data-modal="alerts_edit">
                                <span class="icon event"></span>
                                <span class="text">{{ trans('global.alert') }}</span>
                            </a>
                        </li>
                        @endif


                            <li>
                                <a href="javascript:;" data-url="{{ route('events.do_destroy', ['id' => $item->id]) }}" data-modal="events_do_destroy">
                                    <span class="icon delete"></span>
                                    <span class="text">{{ trans('global.delete') }}</span>
                                </a>
                            </li>
                    </ul>
                </div>
                @endif
            </td>
            <?php
                $arr = $item->toArray();
                $arr['time'] = Formatter::time()->human($item->time);
                unset($arr['geofence'], $arr['device'], $arr['alert'], $arr['poi']);
                if (isset($item->device) ?  : '')
                    $arr['device']['name'] = $item->device->name;
                if (isset($item->geofence->name) ?  : '')
                    $arr['geofence']['name'] = $item->geofence->name;
            ?>
            <script>app.events.add({!! json_encode($arr) !!});</script>
        </tr>
    @endforeach
    @if (method_exists($events, 'nextPageUrl') && $events->nextPageUrl())
        <tr data-toggle="scroll" data-parent=".tab-pane-body" data-url="{{ $events->nextPageUrl() }}" class="events-load-more">
            <td colspan="4"></td>
        </tr>
    @endif
@else
    <tr class="events-row">
        <td class="events-cell no-data" colspan="4">{!!trans('front.no_events')!!}</td>
    </tr>
@endif

@if (!empty($events))
    @foreach ($events as $item)
        @php
            $evText = strtolower(($item->type ?? '') . ' ' . ($item->title ?? ''));
            if (str_contains($evText, 'ignition')) {
                $evKind = str_contains($evText, 'off') ? 'ignition-off' : 'ignition';
            } elseif (str_contains($evText, 'stop')) {
                $evKind = 'stop';
            } elseif (preg_match('/mov|start|drive/', $evText)) {
                $evKind = 'move';
            } else {
                $evKind = 'other';
            }
        @endphp
        <tr class="event-row ev-{{ $evKind }}" data-event-id="{!!$item->id!!}" onClick="app.events.select({!!$item->id!!});">
            <td class="cell-icon">
                <span class="ev-icon">
                    @if ($evKind === 'ignition')
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    @elseif ($evKind === 'ignition-off')
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42A6.92 6.92 0 0 1 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.27 1.08-4.28 2.76-5.56L6.34 5.02A8.94 8.94 0 0 0 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.83-1.3-5.35-3.17-6.83z"/></svg>
                    @elseif ($evKind === 'stop')
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                    @elseif ($evKind === 'move')
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 2l7 18-7-4-7 4z"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2zm6-6V11c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                    @endif
                </span>
            </td>
            <td class="cell-main">
                <span class="cell-device">{{ $item->device->name ?? '' }}</span>
                <span class="cell-title">{{ $item->title }}</span>
                @if (settings('plugins.event_section_address.status'))
                    <span class="cell-address">
                        <svg viewBox="0 0 24 24" width="11" height="11" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
                        <span data-device="address" data-lat="{{ $item->latitude }}" data-lng="{{ $item->longitude }}"></span>
                    </span>
                @endif
            </td>
            <td class="cell-time">
                <span class="cell-date">{{ Formatter::date()->human($item->time) }}</span>
                <span class="cell-clock">{{ Formatter::dtime()->human($item->time) }}</span>
            </td>
            <td class="cell-actions">
                @if(Auth::user()->can('remove', $item))
                <div class="btn-group dropleft droparrow" data-position="fixed">
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
                <span class="cell-dot"></span>
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
    <tr class="event-row">
        <td class="cell-nodata no-data" colspan="4">{!!trans('front.no_events')!!}</td>
    </tr>
@endif

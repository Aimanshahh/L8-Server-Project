@php
    /* Display taxonomy for the existing SMS templates. Categories are resolved
       from the real template names and every count is computed from $items. */
    $etCategories = [
        'event'   => ['label' => 'Event',   'icon' => 'fas fa-calendar-check'],
        'device'  => ['label' => 'Device',  'icon' => 'fas fa-mobile-alt'],
        'user'    => ['label' => 'User',    'icon' => 'fas fa-user'],
        'service' => ['label' => 'Service', 'icon' => 'fas fa-gear'],
        'report'  => ['label' => 'Report',  'icon' => 'fas fa-chart-simple'],
        'sharing' => ['label' => 'Sharing', 'icon' => 'fas fa-share-nodes'],
        'other'   => ['label' => 'Other',   'icon' => 'fas fa-folder'],
    ];

    $etCategoryMap = [
        'event'              => 'event',
        'expired_device'     => 'device',
        'expiring_device'    => 'device',
        'expired_user'       => 'user',
        'expiring_user'      => 'user',
        'phone_verification' => 'user',
        'service_expiration' => 'service',
        'service_expired'    => 'service',
        'report'             => 'report',
        'sharing_link'       => 'sharing',
    ];

    /* SMS notes are stored with the literal escape sequences \r\n from the
       seeder, so normalise whitespace before showing a one-line summary. */
    $etDescription = function ($note) {
        $text = str_replace(['\\r\\n', '\\n', '\\r'], ' ', strip_tags((string) $note));
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return \Illuminate\Support\Str::limit($text, 90);
    };

    $etCollection = $items->getCollection();

    $etGrouped = [];
    foreach ($etCollection as $etItem) {
        $etGrouped[$etCategoryMap[$etItem->name] ?? 'other'][] = $etItem;
    }

    $etOrder = array_flip(array_keys($etCategories));

    uksort($etGrouped, function ($a, $b) use ($etOrder) {
        return ($etOrder[$a] ?? 99) <=> ($etOrder[$b] ?? 99);
    });

    $etFirstKey = array_key_first($etGrouped);
@endphp

<div class="table_error"></div>

@if (count($etCollection))

    <div class="et-tabs">
        <button type="button" class="et-tab is-active" data-category="all">All</button>
        @foreach ($etGrouped as $etKey => $etGroup)
            <button type="button" class="et-tab" data-category="{{ $etKey }}">{{ $etCategories[$etKey]['label'] }}</button>
        @endforeach
    </div>

    <div class="et-sections">
        @foreach ($etGrouped as $etKey => $etGroup)
            <section class="et-section{{ $etKey === $etFirstKey ? ' is-open' : '' }}"
                     data-category="{{ $etKey }}"
                     data-label="{{ $etCategories[$etKey]['label'] }}">

                <header class="et-section__head">
                    <span class="et-section__icon"><i class="{{ $etCategories[$etKey]['icon'] }}"></i></span>
                    <span class="et-section__name">{{ $etCategories[$etKey]['label'] }} ({{ count($etGroup) }})</span>
                    <i class="fas fa-chevron-down et-section__chevron"></i>
                </header>

                <div class="et-section__body" style="{{ $etKey === $etFirstKey ? '' : 'display:none' }}">
                    @foreach ($etGroup as $item)
                        <div class="et-row">
                            <span class="et-row__icon"><i class="{{ $etCategories[$etKey]['icon'] }}"></i></span>

                            <div class="et-row__main">
                                <div class="et-row__name">{!! $item->title !!}</div>
                                <div class="et-row__key">{!! $item->name !!}</div>
                            </div>

                            <div class="et-row__desc">{{ $etDescription($item->note) }}</div>

                            <div class="et-row__meta">
                                {{-- SMS templates have no per-row enable flag; every
                                     template listed here is available for sending. --}}
                                <span class="et-badge et-badge--active">{{ trans('validation.attributes.active') }}</span>
                            </div>

                            <div class="et-row__actions">
                                <div class="btn-group dropdown droparrow" data-position="fixed">
                                    <i class="btn et-row__menu fas fa-ellipsis-v" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                                    <ul class="dropdown-menu">
                                        @if (Auth::user()->can('edit', $item))
                                            <li>
                                                <a href="javascript:" data-modal="{{ $section }}_edit" data-url="{{ route("admin.{$section}.edit", $item->id) }}">{!! trans('global.edit') !!}</a>
                                            </li>
                                        @endif
                                        @if (Auth::user()->can('remove', $item))
                                            <li>
                                                <a href="{{ route("admin.{$section}.destroy", $item->id) }}"
                                                   class="js-confirm-link"
                                                   data-confirm="{!! trans('front.do_delete') !!}"
                                                   data-id="{{ $item->id }}"
                                                   data-method="DELETE">
                                                    {{ trans('global.delete') }}
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

@else
    <div class="et-empty">{!! trans('admin.no_data') !!}</div>
@endif

@include("Admin.Layouts.partials.pagination")

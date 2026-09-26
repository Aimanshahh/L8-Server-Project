<div class="table_error"></div>

<div class="table-responsive">
    <table class="table table-list">
        <thead>
        <tr>
            {!! tableHeader('validation.attributes.active', 'style="width: 1px;"') !!}
            {!! tableHeader('global.language') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>

        <tbody>
        @forelse ($languages as $language)
            <tr data-language="{{ $language['key'] }}"
                data-state="{{ $language['active'] ? 1 : 0 }}"
                data-search="{{ strtolower($language['title'] . ' ' . $language['key']) }}">
                <td>
                    <span class="al-pill {{ $language['active'] ? 'al-pill--on' : 'al-pill--off' }}">
                        {{ $language['active'] ? trans('validation.attributes.active') : trans('front.inactive') }}
                    </span>
                </td>
                <td>
                    <img class="al-flag" src="{{ asset_flag($language['key']) }}" alt="">
                    {{ $language['title'] }}
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                           title="{{ trans('global.edit') }}" aria-label="{{ trans('global.edit') }}"></i>
                        <ul class="dropdown-menu">
                            <li><a href="javascript:" data-modal="{{ $section }}_edit" data-url="{{ route("admin.{$section}.edit", $language['key']) }}">{{ trans('global.edit') }}</a></li>
                            <li><a href="{{ route('admin.translations.show', $language['key']) }}">{{ trans('admin.translate') }}</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="no-data" colspan="3">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-globe"></i></div>
                        <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                    </div>
                </td>
            </tr>
        @endforelse

        <tr class="languages-none is-hidden">
            <td class="no-data" colspan="3">
                <div class="al-empty al-empty--inline">
                    <div class="al-empty__icon"><i class="fas fa-magnifying-glass"></i></div>
                    <p class="al-empty__text">No language matches your search.</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>

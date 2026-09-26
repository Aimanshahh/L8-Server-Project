@if (Session::has('message'))
    <div class="alert alert-success">
        {{ Session::get('message') }}
    </div>
@endif
@if (Session::has('error'))
    <div class="alert alert-danger">
        {{ Session::get('error') }}
    </div>
@endif

<div class="table_error"></div>

<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeader('validation.attributes.ip') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>

        <tbody>
        @forelse ($files as $file)
            <tr data-ip="{{ $file }}" data-search="{{ strtolower($file) }}">
                <td>
                    <span class="al-code">{{ $file }}</span>
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                           title="{{ trans('global.delete') }}" aria-label="{{ trans('global.delete') }}"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="al-danger" href="javascript:"
                                   data-modal="blocked_ips_destroy"
                                   data-url="{{ route('admin.blocked_ips.do_destroy', [$file]) }}">{!! trans('global.delete') !!}</a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="no-data" colspan="2">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-shield-halved"></i></div>
                        <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                    </div>
                </td>
            </tr>
        @endforelse

        <tr class="blocked-ips-none is-hidden">
            <td class="no-data" colspan="2">
                <div class="al-empty al-empty--inline">
                    <div class="al-empty__icon"><i class="fas fa-magnifying-glass"></i></div>
                    <p class="al-empty__text">No blocked address matches your search.</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>

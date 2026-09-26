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
            {!! tableHeader('validation.attributes.active') !!}
            {!! tableHeader('validation.attributes.port') !!}
            {!! tableHeader('validation.attributes.name') !!}
            {!! tableHeader('validation.attributes.extra') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @if (count($ports))
            @foreach($ports as $port)
                @php($extra = count((array) json_decode($port->extra, true)))
                <tr data-port="{{ $port->port }}"
                    data-state="{{ $port->active ? 1 : 0 }}"
                    data-search="{{ strtolower($port->port . ' ' . $port->name) }}">
                    <td>
                        <span class="al-pill {{ $port->active ? 'al-pill--on' : 'al-pill--off' }}">
                            {{ $port->active ? trans('validation.attributes.active') : trans('front.inactive') }}
                        </span>
                    </td>
                    <td><span class="al-code">{{ $port->port }}</span></td>
                    <td>{{ $port->name }}</td>
                    <td>
                        @if ($extra)
                            <span class="al-chip al-chip--neutral">{{ $extra }}</span>
                        @else
                            <span class="al-muted">0</span>
                        @endif
                    </td>
                    <td class="actions">
                        <div class="btn-group dropdown droparrow" data-position="fixed">
                            <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                               title="{{ trans('global.edit') }}" aria-label="{{ trans('global.edit') }}"></i>
                            <ul class="dropdown-menu">
                                <li><a href="javascript:" data-modal="ports_edit" data-url="{{ route('admin.ports.edit', $port->name) }}">{!! trans('global.edit') !!}</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="no-data" colspan="5">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-plug"></i></div>
                        <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                    </div>
                </td>
            </tr>
        @endif
        <tr class="ports-none is-hidden">
            <td class="no-data" colspan="5">
                <div class="al-empty al-empty--inline">
                    <div class="al-empty__icon"><i class="fas fa-magnifying-glass"></i></div>
                    <p class="al-empty__text">No port matches your search.</p>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>

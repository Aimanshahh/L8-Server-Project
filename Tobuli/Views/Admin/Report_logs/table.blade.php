<div class="table_error"></div>
<div class="table-responsive">
    <table class="table table-list" data-toggle="multiCheckbox">
        <thead>
        <tr>
            {!! tableHeaderCheckall([
                'prompt_delete_url' => trans('admin.delete_selected'),
                'prompt_delete_all_url' => trans('admin.delete_all'),
            ]) !!}
            {!! tableHeader('validation.attributes.name') !!}
            {!! tableHeader('validation.attributes.type') !!}
            {!! tableHeader('validation.attributes.format') !!}
            {!! tableHeader('admin.size') !!}
            @if ($showUser)
                {!! tableHeader('global.user') !!}
            @endif
            {!! tableHeader('validation.attributes.send_to_email') !!}
            {!! tableHeader('global.is_send') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @forelse ($logs as $log)
            <tr>
                <td>
                    <div class="checkbox">
                        <input type="checkbox" value="{!! $log->id !!}">
                        <label></label>
                    </div>
                </td>
                <td>
                    <div class="lg-file">
                        <span class="lg-file__icon"><i class="fas fa-file-export"></i></span>
                        <span class="lg-strong">{{ $log->title }}</span>
                    </div>
                </td>
                <td><span class="al-chip">{{ $log->type_text }}</span></td>
                <td><span class="al-chip al-chip--neutral">{{ $log->format_text }}</span></td>
                <td><span class="lg-count">{{ formatBytes($log->size) }}</span></td>
                @if ($showUser)
                    <td><span class="al-muted">{{ $log->user->email ?? '' }}</span></td>
                @endif
                <td><span class="al-muted">{{ $log->email }}</span></td>
                <td>
                    <span class="al-pill {{ $log->is_send ? 'al-pill--on' : 'al-pill--off' }}"
                          title="{{ $log->error }}">{{ $log->is_send ? trans('global.yes') : trans('global.no') }}</span>
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{{ route('admin.report_logs.edit', $log->id) }}">
                                    <i class="fas fa-download"></i> {{ trans('admin.download') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.report_logs.destroy') }}"
                                   class="js-confirm-link al-danger"
                                   data-confirm="{{ trans('admin.do_delete') }}"
                                   data-id="{{ $log->id }}"
                                   data-method="DELETE">
                                    <i class="fas fa-trash-alt"></i> {{ trans('global.delete') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="no-data" colspan="{{ $showUser ? 9 : 8 }}">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-file-export"></i></div>
                        <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@include("Admin.Layouts.partials.pagination", ['items' => $logs])

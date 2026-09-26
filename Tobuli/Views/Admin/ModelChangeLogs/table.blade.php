<div class="table-responsive">
    <table class="table table-list">
        <thead>
        <tr>
            {!! tableHeader(trans('global.user')) !!}
            {!! tableHeader(trans('front.subject')) !!}
            {!! tableHeaderSort($items->sorting, 'subject_type', trans('front.subject') . ' (' . trans('front.type') . ')') !!}
            {!! tableHeaderSort($items->sorting, 'description', trans('front.action')) !!}
            {!! tableHeaderSort($items->sorting, 'log_name', trans('front.last_value')) !!}
            {!! tableHeader(trans('front.count')) !!}
            {!! tableHeaderSort($items->sorting, 'created_at', trans('global.date')) !!}
            {!! tableHeaderSort($items->sorting, 'ip') !!}
            {!! tableHeader('admin.actions', 'style="text-align: right;"') !!}
        </tr>
        </thead>
        <tbody>
        @php
            $actionClasses = [
                'created'       => 'lg-action--green',
                'updated'       => 'lg-action--blue',
                'deleted'       => 'lg-action--red',
                'login_success' => 'lg-action--teal',
                'login_fail'    => 'lg-action--orange',
            ];
        @endphp
        @php /** @var \Tobuli\Entities\ModelChangeLog $item */ @endphp
        @forelse ($items as $item)
            <tr>
                <td class="lg-strong">
                    {!! $item->getCauserName() !!}
                </td>
                <td>
                    {!! $item->getSubjectName() !!}
                </td>
                <td>
                    <span class="al-chip al-chip--neutral">{!! $item->subject_type !!}</span>
                </td>
                <td>
                    <span class="lg-action {{ $actionClasses[$item->description] ?? 'lg-action--neutral' }}">{!! $item->description !!}</span>
                </td>
                <td>
                    <span class="al-muted">{!! $item->log_name !!}</span>
                </td>
                <td>
                    <span class="al-toolbar__count">{{ $item->attributesCount() }}</span>
                </td>
                <td>
                    {!! Formatter::time()->human($item->created_at) !!}
                </td>
                <td>
                    <span class="al-code">{!! $item->ip !!}</span>
                </td>
                <td class="actions">
                    <div class="btn-group dropdown droparrow" data-position="fixed">
                        <i class="btn icon edit" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"></i>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="javascript:" data-modal="model_change_diffs_show"
                                   data-url="{{ route('admin.model_change_logs.show', [$item->id, 1]) }}">
                                    <i class="fas fa-code-compare"></i> {{ trans('front.difference') }}
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('admin.model_change_logs.index', ['search_subjects[]' => $item->subject_type . '-' . $item->subject_id]) }}">
                                    <i class="fas fa-list-ul"></i> {{ trans('front.subject_logs') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="no-data" colspan="9">
                    <div class="al-empty">
                        <div class="al-empty__icon"><i class="fas fa-clipboard-list"></i></div>
                        <p class="al-empty__text">{!! trans('admin.no_data') !!}</p>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@include('admin::Layouts.partials.pagination')

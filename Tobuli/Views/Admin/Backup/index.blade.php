@extends('Admin.Layouts.default')

@section('content')
    <div class="al-page">
        <div class="al-page__header">
            <div class="al-page__title-group">
                <div class="al-page__title-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                </div>
                <div>
                    <h1 class="al-page__title">{{ trans('front.backups') }}</h1>
                    <p class="al-page__subtitle">Monitor automatic and manual database backups</p>
                </div>
            </div>
        </div>

        <div class="al-card">
            <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
            <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

            <div class="al-toolbar">
                <div class="al-toolbar__count">
                    {{ $items->total() }} {{ str_plural('backup', $items->total()) }}
                </div>
            </div>

            <div class="table-responsive" data-table>
                @include('Admin.Backup.table')
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        tables.set_config('table_backup', {
            url:'{{ route("admin.backup.table") }}',
        });
    </script>
@stop

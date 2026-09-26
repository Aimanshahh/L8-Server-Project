@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/device-icons-overrides.css') }}?v=20260918-1">
@stop

@section('content')
<div class="ec-page">

    <div class="ec-page__header">
        <div class="ec-page__title-group">
            <div class="ec-page__title-icon"><i class="fas fa-map-location-dot"></i></div>
            <div>
                <h1 class="ec-page__title">{!! trans('admin.'.$section) !!}</h1>
                <p class="ec-page__subtitle">Icons used to represent devices on the map and in lists</p>
            </div>
        </div>

        <div class="ec-page__actions">
            <a href="javascript:" class="ec-add"
               data-modal="{{ $section }}_create"
               data-url="{{ route("admin.{$section}.create") }}">
                <i class="fas fa-plus"></i> {{ trans('global.add') }}
            </a>
        </div>
    </div>

    <div class="ec-card" id="table_{{ $section }}">
        <div class="panel-body" data-table>
            @include('Admin.'.ucfirst($section).'.table')
        </div>
    </div>
</div>
@stop

@section('javascript')
    <script>
        tables.set_config('table_{{ $section }}', {
            url: '{{ route("admin.{$section}.index") }}',
            delete_url:'{{ route("admin.{$section}.destroy") }}'
        });

        $(document).ready(function() {
            $(document).on('click', '.table-icon .controls a', function () {
                $.ajax({
                    type: 'POST',
                    url: '{{ route("admin.{$section}.destroy") }}',
                    data: {
                        _method: 'DELETE',
                        id: {0:$(this).data('id')}
                    },
                    success: function () {
                        tables.get('table_{{ $section }}');
                    }
                });
            });
        });
    </script>
@stop

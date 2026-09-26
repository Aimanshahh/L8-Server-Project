@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/map-icons-overrides.css') }}?v=20260918-1">
@stop

@section('content')
<div class="mic-page">

    <div class="mic-page__header">
        <div class="mic-page__title-group">
            <div class="mic-page__title-icon"><i class="fas fa-map"></i></div>
            <div>
                <h1 class="mic-page__title">{!! trans('admin.'.$section) !!}</h1>
                <p class="mic-page__subtitle">Icons used for points of interest on the map</p>
            </div>
        </div>

        <div class="mic-page__actions">
            <a href="javascript:" class="mic-add"
               data-url="javascript:void(0)">
                <i class="fas fa-plus"></i> {{ trans('global.add') }}
            </a>
        </div>
    </div>

    <div class="mic-card" id="table_{{ $section }}">
        <div class="panel-heading"><div class="panel-title">{{ count($items) }} icon{{ count($items) == 1 ? '' : 's' }}</div></div>
        <div class="panel-body" data-table>
            @include('Admin.'.ucfirst($section).'.table')
        </div>
    </div>
</div>
@stop

@section('javascript')
<script src="{{ asset('assets/plugins/dropzone/dropzone.min.js') }}"></script>
<script>
tables.set_config('table_{{ $section }}', {
    url: '{{ route("admin.{$section}.index") }}',
    delete_url:'{{ route("admin.{$section}.destroy") }}'
});
$(document).ready(function() {
    Dropzone.options.myDropzone = {
        init: function() {
            this.on("queuecomplete", function() {
                tables.get('table_{{ $section }}');
            });
        }
    };

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
@extends('Admin.Layouts.default')

@section('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/bootstrap-wysihtml5/bootstrap-wysihtml5.css') }}"/>
    <link rel="stylesheet" href="{{ asset_resource('assets/css/admin-templates-overrides.css') }}?v=20260915-2">
@stop

@section('content')
<div class="et-page">

    <div class="et-page__header">
        <div class="et-page__title-group">
            <div class="et-page__title-icon"><i class="fas fa-comment-sms"></i></div>
            <div>
                <h1 class="et-page__title">{!! trans('front.'.$section) !!}</h1>
                <p class="et-page__subtitle">Manage automated SMS notifications and system messages</p>
            </div>
        </div>
    </div>

    <div class="et-card" id="table_{{ $section }}">

        <input type="hidden" name="sorting[sort_by]" value="{{ $items->sorting['sort_by'] }}" data-filter>
        <input type="hidden" name="sorting[sort]" value="{{ $items->sorting['sort'] }}" data-filter>

        <div class="et-toolbar">
            <div class="et-search">
                <i class="fas fa-search"></i>
                <input type="text" name="search_phrase" data-filter="true" autocomplete="off"
                       placeholder="Search templates... (e.g. device_expired, user)">
            </div>

            <div class="et-toolbar__right">
                <select class="et-select" id="et-category-select">
                    <option value="all">All Categories</option>
                </select>

                @if($canCreate)
                    <a href="javascript:" class="et-btn et-btn--primary"
                       data-modal="{{ $section }}_create"
                       data-url="{{ route("admin.{$section}.create") }}">
                        <i class="fas fa-plus"></i> New Template
                    </a>
                @endif
            </div>
        </div>

        <div class="panel-body" data-table>
            @include('Admin.'.ucfirst($section).'.table')
        </div>

    </div>
</div>
@stop

@section('javascript')
<script src="{{ asset('assets/plugins/bootstrap-wysihtml5/wysihtml5-0.3.0.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/plugins/bootstrap-wysihtml5/bootstrap-wysihtml5.js') }}" type="text/javascript"></script>
<script>
    tables.set_config('table_{{ $section }}', {
        url:'{{ route("admin.{$section}.index") }}'
    });

    function {{ $section }}_edit_modal_callback() {
        tables.get('table_{{ $section }}');
    }

    function {{ $section }}_create_modal_callback() {
        tables.get('table_{{ $section }}');
    }

    (function () {
        function rebuildCategorySelect() {
            var $select = $('#et-category-select');

            if (!$select.length)
                return;

            var current = $select.val() || 'all';

            $select.find('option').not(':first').remove();

            $('.et-section').each(function () {
                var category = $(this).attr('data-category');
                var label = $(this).attr('data-label');

                if (category && category !== 'all')
                    $select.append($('<option>').val(category).text(label));
            });

            $select.val($select.find('option[value="' + current + '"]').length ? current : 'all');
        }

        function applyCategoryFilter(category) {
            $('#et-category-select').val(category);

            $('.et-tab')
                .removeClass('is-active')
                .filter('[data-category="' + category + '"]')
                .addClass('is-active');

            $('.et-section').each(function () {
                $(this).toggle(category === 'all' || $(this).attr('data-category') === category);
            });
        }

        function resetSections() {
            var first = $('.et-section').first().attr('data-category');

            $('.et-section').each(function () {
                var open = $(this).attr('data-category') === first;

                $(this).toggleClass('is-open', open);
                $(this).children('.et-section__body').toggle(open);
            });
        }

        function resetListState() {
            applyCategoryFilter('all');
            resetSections();
            rebuildCategorySelect();
        }

        $(document)
            .off('click', '.et-section__head')
            .on('click', '.et-section__head', function () {
                var $section = $(this).closest('.et-section');

                $section.toggleClass('is-open');
                $section.children('.et-section__body').toggle();
            });

        $(document)
            .off('click', '.et-tab')
            .on('click', '.et-tab', function () {
                applyCategoryFilter($(this).attr('data-category') || 'all');
            });

        $(document)
            .off('change', '#et-category-select')
            .on('change', '#et-category-select', function () {
                applyCategoryFilter($(this).val() || 'all');
            });

        $(function () {
            resetListState();

            var target = document.querySelector('#table_{{ $section }} [data-table]');

            if (target && window.MutationObserver) {
                new MutationObserver(function () {
                    resetListState();
                }).observe(target, { childList: true });
            }
        });
    })();
</script>
@stop

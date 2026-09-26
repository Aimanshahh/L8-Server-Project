@php
    // Shared dialog chrome. A view may override any part of this by defining its
    // own @section('icon'), @section('subtitle') or a descriptive @section('title').
    $moTitle = \Tobuli\Helpers\ModalPresenter::title(View::getSection('title'));

    $moSectionIcon = trim((string) View::getSection('icon', ''));
    $moIcon = $moSectionIcon !== '' ? $moSectionIcon : \Tobuli\Helpers\ModalPresenter::icon();

    $moSectionSubtitle = trim((string) View::getSection('subtitle', ''));
    $moSubtitle = $moSectionSubtitle !== ''
        ? $moSectionSubtitle
        : e(\Tobuli\Helpers\ModalPresenter::subtitle() ?: '');
@endphp
<div class="modal-dialog modal-full @yield('modal_class')" style="height: 100%;">
    <div class="modal-content" style="height: 100%;">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span>×</span></button>
            @if ($moIcon)
                <div class="mo-title-icon">{!! $moIcon !!}</div>
            @endif
            <div class="mo-heading">
                <h4 class="modal-title">{!! $moTitle !!}</h4>
                @if ($moSubtitle !== '')
                    <p class="mo-subtitle">{!! $moSubtitle !!}</p>
                @endif
            </div>
        </div>
        <div class="modal-body">
            @yield('body')
        </div>

        @if (View::hasSection('buttons'))
        <div class="modal-footer">
            <div class="buttons">
                @yield('buttons')
            </div>
        </div>
        @endif
    </div>
</div>

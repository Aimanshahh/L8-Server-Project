<?php
$widgets = getActingUser()->getSettings('widgets');

if (empty($widgets)) {
    $widgets = settings('widgets');
}
?>

@if( ! empty($widgets['status']) && ! empty($widgets['list']) )
    <div id="widgets" style="display: none;">
        <a class="btn-collapse btn-collapse-widget" onclick="app.changeSetting('toggleWidgets');"><i></i></a>

        <div class="widgets-content">
            <div class="widget op-device-panel">

                <div class="op-device-header">
                    <div class="op-device-header__row">
                        <div class="op-device-header__icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <span class="op-device-header__name" data-device="name"></span>
                        <span class="op-device-header__speed" data-device="speed"></span>

                        <a class="op-close" href="javascript:" title="Close" onclick="$('#widgets').hide();">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>

                    <ul class="op-tabs" role="tablist">
                        <li class="op-tab is-active" data-op-tab="overview" role="tab">Overview</li>
                        <li class="op-tab" data-op-tab="history" role="tab">History</li>
                    </ul>
                </div>

                <div class="op-tab-panels">
                    <div class="op-tab-panel is-active" data-op-panel="overview">
                        @foreach( $widgets['list'] as $widget)
                            @if (!config("lists.widgets.$widget"))
                                @continue

@endif
                            @include('Frontend.Widgets.'.$widget)
                        @endforeach
                    </div>

                    <div class="op-tab-panel" data-op-panel="history">
                        <div class="op-history" data-op-history>
                            <div class="op-history__empty">Select a vehicle to view its activity</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

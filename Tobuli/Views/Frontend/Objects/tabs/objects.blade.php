<div class="tab-pane-header">
    <div class="op-toolbar">
        <div class="form-group search">
            {!!Form::text('search', null, ['class' => 'form-control', 'placeholder' => trans('front.search'), 'autocomplete' => 'off'])!!}
        </div>

        <button type="button"
                class="op-btn op-btn--ghost op-filters"
                id="op_filters_btn"
                onclick="var h=this.closest('.tab-pane-header');var c=h.classList.toggle('op-filters-collapsed');this.classList.toggle('is-on', !c);this.setAttribute('aria-expanded', c ? 'false' : 'true');"
                aria-expanded="true"
                title="{{ trans('admin.filters') }}">
            <i class="fas fa-sliders-h"></i>
            <span>{{ trans('admin.filters') }}</span>
        </button>
    </div>

    <div class="status-filters" id="device_status_filters">
        <button type="button" class="status-chip active" data-status-filter="all">
            <span class="chip-icon"><i class="fas fa-car"></i></span>
            <span class="chip-label">{{ trans('front.total') }}</span>
            <span class="chip-count" data-count="all">0</span>
        </button>
        <button type="button" class="status-chip status-chip--running" data-status-filter="moving">
            <span class="chip-icon"><i class="fas fa-car"></i></span>
            <span class="chip-label">{{ trans('front.running') }}</span>
            <span class="chip-count" data-count="moving">0</span>
        </button>
        <button type="button" class="status-chip status-chip--idle" data-status-filter="idle">
            <span class="chip-icon"><i class="fas fa-car"></i></span>
            <span class="chip-label">{{ trans('front.idle') }}</span>
            <span class="chip-count" data-count="idle">0</span>
        </button>
        <button type="button" class="status-chip status-chip--stopped" data-status-filter="stopped">
            <span class="chip-icon"><i class="fas fa-car"></i></span>
            <span class="chip-label">{{ trans('front.stopped') }}</span>
            <span class="chip-count" data-count="stopped">0</span>
        </button>
        <button type="button" class="status-chip status-chip--nodata" data-status-filter="offline">
            <span class="chip-icon"><i class="fas fa-satellite-dish"></i></span>
            <span class="chip-label">{{ trans('front.no_data') }}</span>
            <span class="chip-count" data-count="offline">0</span>
        </button>
    </div>
</div>

<div class="tab-pane-body">
    <div id="ajax-items"></div>
</div>
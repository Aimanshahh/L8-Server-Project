<div class="tab-pane-header">
    <div class="op-toolbar">
        <div class="form-group search">
            {!!Form::text('search', null, ['class' => 'form-control', 'placeholder' => trans('front.search'), 'autocomplete' => 'off'])!!}
        </div>

        <label class="op-auto-refresh" title="Refresh the device list every 12 seconds"
               style="display:inline-flex;align-items:center;gap:5px;margin:0 8px;font-size:12px;cursor:pointer;white-space:nowrap;">
            <input type="checkbox" id="op_auto_refresh" style="margin:0;">
            <span>Auto</span>
        </label>

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

<script>
(function () {
    var KEY = 'op_auto_refresh', EVERY = 12000;
    var timer = null, busy = false;

    function ready() {
        return window.jQuery && window.app && app.urls && app.urls.devicesSidebar &&
               typeof refreshDeviceStatusFilters === 'function';
    }

    function quietRefresh() {
        if (busy || document.hidden) return;

        var search = document.querySelector('#objects_tab input[name="search"]');
        if (search && document.activeElement === search) return;

        var $c = $('#ajax-items');
        if (!$c.length) return;

        busy = true;
        var $scroller = $c.closest('.tab-pane-body');

        $.ajax({
            type: 'GET',
            dataType: 'html',
            cache: false,
            url: app.urls.devicesSidebar.replace(/\/+$/, '') + '/items',
            data: { s: search ? search.value : '' },
            timeout: 20000,
            success: function (html) {
                var top = $scroller.length ? $scroller.scrollTop() : 0;
                var activeId = app.devices ? app.devices.single : null;

                $c.html($('<div></div>').html(html));

                if (activeId)
                    $c.find('[data-device-id="' + activeId + '"]').addClass('active');

                if (typeof initComponents === 'function')
                    initComponents($c);

                refreshDeviceStatusFilters();

                if ($scroller.length) $scroller.scrollTop(top);
            },
            complete: function () { busy = false; },
            error: function (x) { if (x.status === 401) window.location.reload(); }
        });
    }

    function stop()  { if (timer) { clearInterval(timer); timer = null; } }
    function start() { stop(); quietRefresh(); timer = setInterval(quietRefresh, EVERY); }

    function init() {
        var cb = document.getElementById('op_auto_refresh');
        if (!cb) return;

        var on = false;
        try { on = localStorage.getItem(KEY) === '1'; } catch (e) {}
        cb.checked = on;
        if (on) start();

        cb.addEventListener('change', function () {
            try { localStorage.setItem(KEY, cb.checked ? '1' : '0'); } catch (e) {}
            cb.checked ? start() : stop();
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden && cb.checked) quietRefresh();
        });
    }

    var tries = 0, w = setInterval(function () {
        if (ready()) { clearInterval(w); init(); }
        else if (++tries > 60) clearInterval(w);
    }, 500);
})();
</script>

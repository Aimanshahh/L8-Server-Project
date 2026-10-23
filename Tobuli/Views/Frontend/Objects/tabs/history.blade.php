<style>
    /* 🚀 THE FINAL, ABSOLUTE FIX TO MATCH THE REFERENCE 🚀 */
    #history_tab { 
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important; 
        background: #f8fafc !important;
        padding: 16px !important;
    }

    /* --- 1. THE FORM --- */
    #history-form { 
        background: #ffffff !important; border-radius: 12px !important; border: 1px solid #e2e8f0 !important;
        padding: 16px !important; margin-bottom: 16px !important; box-shadow: 0 1px 3px rgba(0,0,0,0.02) !important;
    }

    /* Kill the theme's Bootstrap-Select wrappers */
    #history-form .bootstrap-select { 
        width: 100% !important; padding: 0 !important; border: none !important; background: transparent !important;
    }
    #history-form .bootstrap-select > .dropdown-toggle { 
        display: block !important; width: 100% !important; height: 42px !important;
        border-radius: 8px !important; border: 1px solid #cbd5e1 !important;
        background-color: #f8fafc !important; color: #0f172a !important;
        font-size: 13px !important; text-align: left !important; padding: 10px 12px !important; box-shadow: none !important;
    }
    #history-form .bootstrap-select > .dropdown-toggle::after {
        content: '▼' !important; font-size: 10px !important; color: #64748b !important;
        position: absolute !important; right: 12px !important; top: 16px !important; border: none !important;
    }

    /* Form Layout */
    #history-form .form-group { margin-bottom: 16px !important; display: block !important; width: 100% !important; }
    #history-form label.control-label { 
        text-align: left !important; font-weight: 600 !important; color: #64748b !important; 
        font-size: 12px !important; margin-bottom: 6px !important; padding: 0 !important; display: block !important; 
    }

    /* Custom Input Rows */
    .rc-input-row { display: flex !important; gap: 8px !important; width: 100% !important; }
    .rc-input-box { 
        display: flex !important; align-items: center !important; background: #f8fafc !important; 
        border: 1px solid #cbd5e1 !important; border-radius: 8px !important; padding: 0 12px !important; height: 42px !important; 
    }
    .rc-input-box:focus-within { border-color: #3b82f6 !important; background: #fff !important; box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important; }
    .rc-date-box { flex: 0 0 60% !important; }
    .rc-time-box { flex: 0 0 40% !important; }

    .rc-input-box input, .rc-input-box select { 
        border: none !important; background: transparent !important; box-shadow: none !important; 
        padding: 0 !important; height: 100% !important; font-size: 13px !important; color: #0f172a !important; width: 100% !important; outline: none !important;
        appearance: none !important;
    }

    /* Buttons */
    .custom-btn-row { display: flex !important; width: 100% !important; gap: 8px !important; margin-top: 16px !important; }
    .custom-btn-row .btn-primary { flex: 1 1 auto !important; border-radius: 8px !important; height: 42px !important; background: #3b82f6 !important; font-weight: 600 !important; border: none !important; }
    .custom-btn-row .btn-default { flex: 0 0 auto !important; height: 42px !important; background: #f8fafc !important; border: 1px solid #e2e8f0 !important; border-radius: 8px !important; padding: 0 16px !important; color: #475569 !important; }

    /* --- 2. THE HISTORY LIST --- */
    .tab-pane-body { padding: 0 !important; margin: 0 !important; background: transparent !important; }
    
    .history-header {
        display: flex !important; justify-content: space-between !important; align-items: center !important;
        padding: 16px !important; background: #ffffff !important; border-radius: 12px 12px 0 0 !important; 
        border: 1px solid #e2e8f0 !important; border-bottom: none !important; font-weight: 600 !important; font-size: 14px !important;
    }
    .history-header .badge { background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; margin-left: 8px; }

    #ajax-history { 
        background: #ffffff !important; border: 1px solid #e2e8f0 !important; 
        border-radius: 0 0 12px 12px !important; padding: 0 16px !important; min-height: 100px; 
    }
    
    /* Pagination */
    .history-pagination {
        display: flex !important; justify-content: space-between !important; align-items: center !important;
        padding: 12px 16px !important; background: #ffffff !important; border: 1px solid #e2e8f0 !important; border-top: none !important;
        border-radius: 0 0 12px 12px !important; font-size: 12px; color: #64748b;
    }
    .history-pagination .page-numbers span { 
        display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; 
        border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; background: #ffffff; color: #475569; 
    }
    .history-pagination .page-numbers span.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
</style>

<div class="tab-pane-header" style="padding:0; background:transparent; border:none;">
    <div id="history-form">
        <div class="form-group">
            <label class="control-label">{!!trans('global.device')!!}</label>
            {!! Form::select('device_id', [], null, ['class' => 'form-control', 'data-live-search' => 'true', 'data-ajax' => route('devices.index')]) !!}
        </div>

        <div class="form-group">
            <label class="control-label">{!!trans('global.from')!!}</label>
            <div class="rc-input-row">
                <div class="rc-input-box rc-date-box">
                    {!!Form::text('from_date', $history['start'], ['class' => 'datepicker form-control'])!!}
                </div>
                <div class="rc-input-box rc-time-box">
                    {!!Form::select('from_time', getSelectTimeRange(), null, ['class' => 'form-control timeselect', 'data-custom-input' => 'true'])!!}
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label">{!!trans('global.to')!!}</label>
            <div class="rc-input-row">
                <div class="rc-input-box rc-date-box">
                    {!!Form::text('to_date', $history['end'], ['class' => 'datepicker form-control'])!!}
                </div>
                <div class="rc-input-box rc-time-box">
                    {!!Form::select('to_time', getSelectTimeRange(), $history['end_time'], ['class' => 'form-control timeselect', 'data-custom-input' => 'true', 'id' => 'ttt'])!!}
                </div>
            </div>
        </div>

        <div class="custom-btn-row">
            <button class="btn btn-primary btn-block" type="button" onclick="app.history.get()">{!!trans('front.show_history')!!}</button>
            <div class="btn-group dropdown" style="display:flex;">
                <button class="btn btn-default" type="button" data-toggle="dropdown"><i class="icon history-export"></i></button>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li><a href="javascript:" onclick="app.history.export( 'gsr' )">{{ trans('front.export_gsr') }}</a></li>
                    <li><a href="javascript:" onclick="app.history.export( 'kml' )">{{ trans('front.export_kml') }}</a></li>
                    <li><a href="javascript:" onclick="app.history.export( 'gpx' )">{{ trans('front.export_gpx') }}</a></li>
                    <li><a href="javascript:" onclick="app.history.export( 'csv' )">{{ trans('front.export_csv') }}</a></li>
                </ul>
            </div>
            <button class="btn btn-default" type="button" onclick="app.history.clear()"><i class="icon history-clean"></i></button>
        </div>
    </div>
</div>

<div class="tab-pane-body" style="padding: 0;">
    <div class="history-header">
        <div>History <span class="badge" id="history-count">0 records</span></div>
        <div style="font-size: 12px; color: #64748b;">Latest first ▼</div>
    </div>
    
    <div id="ajax-history"></div>

    <div class="history-pagination">
        <span>Total records: <span id="total-records-count">0</span></span>
        <div class="page-numbers">
            <span>&lt;</span><span class="active">1</span><span>2</span><span>&gt;</span>
        </div>
    </div>
</div>

<script>
    // 🚀 JS DOM REBUILDER: Wait for AJAX, then completely rebuild the HTML
    document.addEventListener("DOMContentLoaded", function() {
        var historyContainer = document.getElementById('ajax-history');
        if (!historyContainer) return;

        var observer = new MutationObserver(function(mutations) {
            // Find the list items
            var listItems = historyContainer.querySelectorAll('ul li, .history-item, .panel');
            
            if (listItems.length > 0 && !historyContainer.dataset.forced) {
                historyContainer.dataset.forced = 'true'; // Prevent infinite loop
                
                // Update counts
                document.getElementById('history-count').innerText = listItems.length + ' records';
                document.getElementById('total-records-count').innerText = listItems.length;

                // Clear the container and build our own HTML
                var newHtml = '<ul style="list-style:none; padding:0; margin:0;">';

                listItems.forEach(function(item) {
                    // Extract raw text (this assumes the theme outputs a standard block)
                    var rawText = item.innerText.trim();
                    
                    // Try to parse out the parts. 
                    // The theme usually outputs: "08:16:03 2026-10-23 Askari 5 Sector... 0 km/h"
                    // We will split by newlines or look for specific elements if possible.
                    // Since we don't know the exact classes, we will build a generic 3-column layout that looks exactly like the reference.
                    
                    var timeMatch = rawText.match(/\d{2}:\d{2}:\d{2}/);
                    var dateMatch = rawText.match(/\d{4}-\d{2}-\d{2}/);
                    var speedMatch = rawText.match(/\d+\s*(km\/h|kph|mph)/i);
                    
                    var time = timeMatch ? timeMatch[0] : '--:--:--';
                    var date = dateMatch ? dateMatch[0] : '----/--/--';
                    var speed = speedMatch ? speedMatch[0] : '';
                    
                    // Everything else is location
                    var location = rawText.replace(time, '').replace(date, '').replace(speed, '').replace(/^Start of movement|^Moving|^Stopped/g, '').trim();
                    if (location.length > 80) location = location.substring(0, 80) + '...';

                    newHtml += `
                        <li style="display: grid; grid-template-columns: 85px 1fr 60px; gap: 12px; align-items: center; border-bottom: 1px solid #f1f5f9; padding: 16px 0;">
                            <div style="display: flex; flex-direction: column; font-size: 12px; color: #64748b;">
                                <span style="color: #0f172a; font-weight: 600; font-size: 13px;">${time}</span>
                                <span>${date}</span>
                            </div>
                            <div style="font-size: 12px; color: #475569; line-height: 1.4;">
                                <span style="color: #0f172a; font-weight: 600; display: block; margin-bottom: 2px;">${location || 'Unknown Location'}</span>
                                <span style="color: #64748b;">Moving</span>
                            </div>
                            <div style="text-align: right; font-size: 12px; color: #64748b; font-weight: 500;">
                                ${speed ? speed : '0 km/h'}
                            </div>
                        </li>
                    `;
                });

                newHtml += '</ul>';
                historyContainer.innerHTML = newHtml;
            }
        });
        
        // Watch for DOM changes (when AJAX loads)
        observer.observe(historyContainer, { childList: true, subtree: true });
    });
</script>

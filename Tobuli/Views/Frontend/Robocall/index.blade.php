@extends('Frontend.Layouts.default')

@section('header-menu-items')
    <li class="nav__item"><a class="nav__link" href="{!! route('objects.index') !!}" role="button"><span class="icon map"></span><span class="nav__link-txt">{!! trans('admin.map') !!}</span></a></li>
    <li class="nav__item"><a class="nav__link" href="{!! route('robocall.index') !!}" role="button"><span class="icon call_action"></span><span class="nav__link-txt">Robocall</span></a></li>
    @if (isAdmin())
        <li class="nav__item"><a class="nav__link" href="{!! route('admin') !!}" role="button"><span class="icon admin"></span><span class="nav__link-txt">{!! trans('global.admin') !!}</span></a></li>
    @endif
    <li class="nav__item"><a class="nav__link" href="javascript:" data-url="{!! route('my_account_settings.edit') !!}" data-modal="my_account_settings_edit"><span class="icon setup"></span><span class="nav__link-txt">Setup</span></a></li>
@stop

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* 🚀 FULL SCREEN OVERRIDES 🚀 */
        /* Force common parent wrappers to remove their grey borders/padding */
        .app-content, .content-wrapper, .content-body, .main-content, .page-container, .content {
            padding: 0 !important;
            margin: 0 !important;
            background-color: transparent !important;
        }

        #robocall-dashboard * { box-sizing: border-box; }

        :root {
            --bg: #f8fafc; --card: #ffffff; --text: #0f172a; --muted: #64748b;
            --border: #e2e8f0; --primary: #3b82f6; --success: #10b981; --danger: #ef4444; --warning: #f59e0b;
            --hover: #f1f5f9; --input-bg: #ffffff;
        }

        body.dark #robocall-dashboard, body.dark-mode #robocall-dashboard, body.dark-theme #robocall-dashboard,
        .dark #robocall-dashboard, .dark-mode #robocall-dashboard, .skin-dark #robocall-dashboard {
            --bg: #0f172a; --card: #1e293b; --text: #f8fafc; --muted: #94a3b8;
            --border: #334155; --hover: #334155; --input-bg: #0f172a;
        }

        /* Make the dashboard take up the full width and height */
        #robocall-dashboard {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg); color: var(--text);
            width: 100%;
            min-height: 100vh;
            padding: 30px 40px;
            transition: background-color 0.3s, color 0.3s;
            
            /* Keep the scrollbar we just fixed */
            height: calc(100vh - 50px) !important; 
            overflow-y: auto !important; 
            overflow-x: hidden;
        }
        
        /* Make the scrollbar look nice and grey */
        #robocall-dashboard::-webkit-scrollbar { width: 10px; }
        #robocall-dashboard::-webkit-scrollbar-track { background: transparent; }
        #robocall-dashboard::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        #robocall-dashboard::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Changed max-width to 100% so it doesn't center with grey borders */
        .rc-container { width: 100%; margin: 0 auto; display: flex; gap: 24px; align-items: flex-start; }
        .rc-main { flex: 1 1 auto; min-width: 0; width: 100%; }
        .rc-sidebar { width: 340px; flex-shrink: 0; display: flex; flex-direction: column; gap: 24px; position: sticky; top: 0; }
        
        @media (max-width: 1100px) {
            .rc-container { flex-direction: column; }
            .rc-sidebar { width: 100%; position: static; }
        }
        
        .rc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; flex-wrap: wrap; gap: 16px; }
        .rc-header h3 { margin: 0 0 6px; font-size: 32px; font-weight: 800; letter-spacing: -0.03em; }
        .rc-muted { color: var(--muted); font-size: 14px; font-weight: 500; }
        
        .rc-btn { padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: 1px solid var(--border); display: inline-flex; align-items: center; gap: 8px; text-decoration: none !important; }
        .rc-btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 10px rgba(59,130,246,0.3); }
        .rc-btn-primary:hover { background: #2563eb; transform: translateY(-1px); color: #fff; }
        .rc-btn-outline { background: var(--card); color: var(--text); }
        .rc-btn-outline:hover { background: var(--hover); }
        
        .rc-stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
        .rc-kpi { background: var(--card); padding: 20px; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 2px 4px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
        .rc-kpi-header { display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 2; }
        .rc-kpi-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .rc-kpi-title { font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em; margin-bottom: 8px; }
        .rc-kpi-value { font-size: 28px; font-weight: 800; color: var(--text); line-height: 1; margin-bottom: 8px; }
        .rc-kpi-sub { font-size: 13px; color: var(--muted); font-weight: 500; position: relative; z-index: 2; }
        .rc-sparkline-wrap { position: absolute; bottom: 10px; right: 10px; width: 80px; height: 30px; z-index: 1; opacity: 0.8; }
        .rc-sparkline-wrap canvas { width: 100% !important; height: 100% !important; }

        .rc-card { background: var(--card); border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 24px; }
        
        .rc-toolbar { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 12px; }
        .rc-tabs { display: flex; gap: 8px; }
        .rc-tab { padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; color: var(--muted); transition: all 0.2s; border: 1px solid transparent; }
        .rc-tab:hover { background: var(--hover); }
        .rc-tab.active { background: rgba(59, 130, 246, 0.1); color: var(--primary); border-color: rgba(59, 130, 246, 0.2); }
        
        .rc-filters { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .rc-input { padding: 10px 14px; border-radius: 8px; border: 1px solid var(--border); background: var(--input-bg); color: var(--text); font-size: 14px; outline: none; }
        .rc-input:focus { border-color: var(--primary); }

        .rc-switch-container { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--muted); cursor: pointer; margin-right: 8px; }
        .rc-switch { position: relative; display: inline-block; width: 36px; height: 20px; }
        .rc-switch input { opacity: 0; width: 0; height: 0; }
        .rc-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--border); transition: .4s; border-radius: 34px; }
        .rc-slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        .rc-switch input:checked + .rc-slider { background-color: var(--primary); }
        .rc-switch input:checked + .rc-slider:before { transform: translateX(16px); }
        
        .rc-table-wrapper { overflow-x: auto !important; width: 100%; }

        .rc-table { width: 100%; border-collapse: collapse; text-align: left; }
        .rc-table th { padding: 16px 24px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); background: var(--card); position: sticky; top: 0; z-index: 10; white-space: nowrap; }
        .rc-table td { padding: 18px 24px; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--text); vertical-align: middle; white-space: nowrap; }
        .rc-table tr:hover td { background: var(--hover); }
        
        .rc-badge { display: inline-flex; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; align-items: center; gap: 6px; }
        .rc-badge-success { background: rgba(16, 185, 129, 0.15); color: var(--success); }
        .rc-badge-danger { background: rgba(239, 68, 68, 0.15); color: var(--danger); }
        .rc-badge-warning { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
        .rc-badge-info { background: rgba(59, 130, 246, 0.15); color: var(--primary); }
        .rc-amount { font-weight: 700; color: var(--success); background: rgba(16, 185, 129, 0.1); padding: 4px 10px; border-radius: 6px; font-size: 13px; }
        
        .rc-widget { background: var(--card); border-radius: 16px; border: 1px solid var(--border); padding: 24px; }
        .rc-widget-title { font-size: 16px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .rc-activity-item { display: flex; gap: 16px; padding-bottom: 16px; margin-bottom: 16px; border-bottom: 1px solid var(--border); }
        .rc-activity-dot { width: 10px; height: 10px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
        .rc-activity-content { flex: 1; }
        .rc-activity-title { font-weight: 600; font-size: 14px; margin-bottom: 2px; }
        .rc-activity-desc { font-size: 13px; color: var(--muted); }
        
        .rc-pagination { padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); }
        .rc-modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; }
        .rc-modal { background: var(--card); width: 100%; max-width: 600px; border-radius: 16px; padding: 32px; border: 1px solid var(--border); }
        
        @media (max-width: 1200px) { .rc-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 600px) { .rc-stats-grid { grid-template-columns: 1fr; } }
    </style>

    <!-- JS SCRIPT TO UNLOCK THE THEME'S BROKEN SCROLL AND PADDING -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let el = document.getElementById('robocall-dashboard');
            let parent = el.parentElement;
            while (parent) {
                if (parent.tagName !== 'BODY' && parent.tagName !== 'HTML') {
                    let style = window.getComputedStyle(parent);
                    // Remove padding/margins that cause grey borders
                    if (parseInt(style.paddingLeft) > 0 || parseInt(style.paddingRight) > 0) {
                        parent.style.paddingLeft = '0';
                        parent.style.paddingRight = '0';
                    }
                    if (parseInt(style.marginLeft) > 0 || parseInt(style.marginRight) > 0) {
                        parent.style.marginLeft = '0';
                        parent.style.marginRight = '0';
                    }
                    // Unlock scrolling
                    if (style.overflow === 'hidden' || style.overflowY === 'hidden') {
                        parent.style.overflow = 'visible';
                        parent.style.overflowY = 'visible';
                    }
                    if (style.height.includes('vh') || style.height.includes('px')) {
                        parent.style.height = 'auto';
                    }
                }
                parent = parent.parentElement;
            }
        });
    </script>

    <div id="robocall-dashboard">
        <div class="rc-container">
            
            <div class="rc-main">
                <div class="rc-header">
                    <div>
                        <h3>Robocall Dashboard</h3>
                        <div class="rc-muted">Monitor incoming call events and payloads from your provider.</div>
                    </div>
                    <div style="display:flex; gap:12px;">
                        <button class="rc-btn rc-btn-outline" onclick="document.getElementById('urlModal').style.display='flex'">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            Webhook Settings
                        </button>
                        <button class="rc-btn rc-btn-primary" id="rc-test">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            Send Test Payload
                        </button>
                    </div>
                </div>

                @php
                    // NORMALIZED STATS LOGIC
                    $answeredCount = 0;
                    $busyCount = 0;
                    $noAnswerCount = 0;
                    $totalAmount = 0;

                    foreach($calls as $c) {
                        $ev = strtoupper($c['event'] ?? '');
                        if (in_array($ev, ['COMPLETED', 'CALL_COMPLETED', 'ANSWERED', 'SUCCESS'])) {
                            $answeredCount++;
                        } elseif ($ev === 'BUSY') {
                            $busyCount++;
                        } else {
                            $noAnswerCount++;
                        }
                        $totalAmount += (int) ($c['amount'] ?? 0);
                    }
                    $missedCount = $busyCount + $noAnswerCount;
                    $rate = $total > 0 ? round(($answeredCount / $total) * 100) : 0;
                @endphp

                <div class="rc-stats-grid">
                    <div class="rc-kpi">
                        <div class="rc-kpi-header">
                            <div>
                                <div class="rc-kpi-title">Total Payloads</div>
                                <div class="rc-kpi-value">{{ $total }}</div>
                            </div>
                            <div class="rc-kpi-icon" style="background:rgba(59,130,246,0.1); color:var(--primary);">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path></svg>
                            </div>
                        </div>
                        <div class="rc-kpi-sub">All time events</div>
                        <div class="rc-sparkline-wrap"><canvas id="spark1"></canvas></div>
                    </div>

                    <div class="rc-kpi" style="background: rgba(16,185,129,0.05);">
                        <div class="rc-kpi-header">
                            <div>
                                <div class="rc-kpi-title">Answer Rate</div>
                                <div class="rc-kpi-value">{{ $rate }}%</div>
                            </div>
                            <div class="rc-kpi-icon" style="background:rgba(16,185,129,0.15); color:var(--success);">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path></svg>
                            </div>
                        </div>
                        <div class="rc-kpi-sub" style="margin-bottom:8px;">{{ $answeredCount }} of {{ $total }} calls answered</div>
                        <div style="height:6px; background:var(--border); border-radius:99px; width:100%; position:relative; z-index:2;">
                            <div style="height:100%; width:{{ $rate }}%; background:var(--success); border-radius:99px;"></div>
                        </div>
                    </div>

                    <div class="rc-kpi" style="background: rgba(239,68,68,0.05);">
                        <div class="rc-kpi-header">
                            <div>
                                <div class="rc-kpi-title">Missed / Busy</div>
                                <div class="rc-kpi-value">{{ $missedCount }}</div>
                            </div>
                            <div class="rc-kpi-icon" style="background:rgba(239,68,68,0.15); color:var(--danger);">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path><path d="M15.707 9.293a1 1 0 00-1.414 1.414l.707.707-.707.707a1 1 0 001.414 1.414l.707-.707.707.707a1 1 0 001.414-1.414l-.707-.707.707-.707a1 1 0 00-1.414-1.414l-.707.707-.707-.707z"></path></svg>
                            </div>
                        </div>
                        <div class="rc-kpi-sub" style="color:var(--danger);">Calls not answered</div>
                        <div class="rc-sparkline-wrap"><canvas id="spark2"></canvas></div>
                    </div>

                    <div class="rc-kpi">
                        <div class="rc-kpi-header">
                            <div>
                                <div class="rc-kpi-title">Total Amount</div>
                                <div class="rc-kpi-value" style="font-size:24px;">PKR {{ number_format($totalAmount) }}</div>
                            </div>
                            <div class="rc-kpi-icon" style="background:rgba(139,92,246,0.1); color:#8b5cf6;">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path></svg>
                            </div>
                        </div>
                        <div class="rc-kpi-sub">Generated from calls</div>
                        <div class="rc-sparkline-wrap"><canvas id="spark3"></canvas></div>
                    </div>
                </div>

                <div class="rc-card">
                    <div class="rc-toolbar">
                        <div class="rc-tabs">
                            <div class="rc-tab active" data-filter="all">All Calls</div>
                            <div class="rc-tab" data-filter="answered">Answered</div>
                            <div class="rc-tab" data-filter="missed">Missed</div>
                            <div class="rc-tab" data-filter="callback">Callback</div>
                        </div>
                        <div class="rc-filters">
                            <label class="rc-switch-container" title="Automatically reload the page every 15 seconds">
                                <div class="rc-switch">
                                    <input type="checkbox" id="rc-auto-refresh">
                                    <span class="rc-slider"></span>
                                </div>
                                Auto-Refresh
                            </label>
                            <input type="text" id="rc-search" class="rc-input" placeholder="Search...">
                        </div>
                    </div>

                    <div class="rc-table-wrapper">
                        <table class="rc-table">
                            <thead>
                            <tr>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Call To</th>
                                <th>Duration</th>
                                <th>DTMF</th>
                                <th>Alert ID</th>
                                <th>Alert Type</th>
                                <th>Amount</th>
                                <th>Call ID</th>
                            </tr>
                            </thead>
                            <tbody id="rc-table-body">
                            @forelse ($calls as $i => $call)
                                @php $localTime = \Carbon\Carbon::parse($call['received_at'] ?? 'now')->timezone('Asia/Karachi'); @endphp
                                <tr class="rc-row" data-status="{{ strtoupper($call['event'] ?? '') }}" data-dtmf="{{ $call['dtmf'] ?? '' }}">
                                    <td>
                                        <div style="font-weight:600;">{{ $localTime->format('M d, Y') }}</div>
                                        <div class="rc-muted" style="font-size:12px;">{{ $localTime->format('H:i:s') }}</div>
                                    </td>
                                    <td>
                                        @if(in_array(strtoupper($call['event'] ?? ''), ['COMPLETED', 'CALL_COMPLETED', 'ANSWERED']))
                                            <span class="rc-badge rc-badge-success">ANSWERED</span>
                                        @elseif(strtoupper($call['event'] ?? '') == 'BUSY')
                                            <span class="rc-badge rc-badge-warning">BUSY</span>
                                        @else
                                            <span class="rc-badge rc-badge-danger">NO ANSWER</span>
                                        @endif
                                    </td>
                                    <td style="font-weight:600;">{{ $call['to'] ?? '—' }}</td>
                                    <td>{{ isset($call['duration']) ? $call['duration'] . 's' : '—' }}</td>
                                    <td>
                                        @if(!empty($call['dtmf'])) 
                                            <span class="rc-badge rc-badge-info">{{ $call['dtmf'] }}</span>
                                        @else
                                            <span class="rc-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $call['order_id'] ?? '—' }}</td>
                                    <td style="font-weight:500;">{{ $call['order_number'] ?? '—' }}</td>
                                    <td>
                                        @if(isset($call['amount']))
                                            <span class="rc-amount">PKR {{ number_format($call['amount']) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="rc-muted" style="font-size:13px; font-family:monospace;">{{ \Illuminate\Support\Str::limit($call['call_id'] ?? '—', 12) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="rc-muted" style="padding:60px;text-align:center; font-size:15px;">
                                        No call events received yet. Waiting for provider...
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="rc-pagination">
                        <div class="rc-muted">Showing {{ count($calls) }} records</div>
                    </div>
                </div>
            </div>

            <div class="rc-sidebar">
                <div class="rc-widget">
                    <div class="rc-widget-title">
                        <svg width="20" height="20" fill="var(--primary)" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        Call Status Breakdown
                    </div>
                    
                    <div style="position:relative; height:220px; width:100%; margin-bottom:20px; display:flex; justify-content:center; align-items:center;">
                        <canvas id="donutChart"></canvas>
                        <div style="position:absolute; text-align:center; pointer-events:none;">
                            <div style="font-size:36px; font-weight:800; color:var(--text); line-height:1;">{{ $total }}</div>
                            <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; margin-top:4px; letter-spacing:0.05em;">Total Calls</div>
                        </div>
                    </div>
                    
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap:8px; font-size:14px;">
                                <span style="width:10px; height:10px; border-radius:50%; background:var(--success); flex-shrink:0;"></span> Answered
                            </div>
                            <div style="font-weight:600;">{{ $answeredCount }}</div>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap:8px; font-size:14px;">
                                <span style="width:10px; height:10px; border-radius:50%; background:var(--danger); flex-shrink:0;"></span> No Answer
                            </div>
                            <div style="font-weight:600;">{{ $noAnswerCount }}</div>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap:8px; font-size:14px;">
                                <span style="width:10px; height:10px; border-radius:50%; background:var(--warning); flex-shrink:0;"></span> Busy
                            </div>
                            <div style="font-weight:600;">{{ $busyCount }}</div>
                        </div>
                    </div>
                </div>

                <div class="rc-widget">
                    <div class="rc-widget-title">
                        <svg width="20" height="20" fill="var(--primary)" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                        Recent Activity
                    </div>
                    @foreach(collect($calls)->take(4) as $call)
                        @php $time = \Carbon\Carbon::parse($call['received_at'] ?? 'now')->timezone('Asia/Karachi'); @endphp
                        <div class="rc-activity-item">
                            <div class="rc-activity-dot" style="background: {{ in_array(strtoupper($call['event'] ?? ''), ['COMPLETED', 'CALL_COMPLETED', 'ANSWERED']) ? 'var(--success)' : 'var(--danger)' }};"></div>
                            <div class="rc-activity-content">
                                <div class="rc-activity-title">
                                    {{ in_array(strtoupper($call['event'] ?? ''), ['COMPLETED', 'CALL_COMPLETED', 'ANSWERED']) ? 'Call answered' : 'Call missed' }}
                                </div>
                                <div class="rc-activity-desc">{{ $call['to'] ?? '' }}</div>
                            </div>
                            <div style="font-size:12px; color:var(--muted);">{{ $time->format('H:i') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div id="urlModal" class="rc-modal-overlay">
        <div class="rc-modal">
            <h4 style="margin:0 0 10px; font-size:20px;">Webhook Settings</h4>
            <p class="rc-muted" style="margin-bottom:24px;">Copy this URL and paste it into your Robocall provider's panel.</p>
            <div style="display:flex; gap:12px; margin-bottom:24px;">
                <input class="rc-input" id="rc-webhook-url" type="text" readonly value="{{ $webhookUrl }}" style="flex:1; font-family:monospace;">
                <button class="rc-btn rc-btn-primary" id="rc-copy">Copy</button>
            </div>
            <div style="display:flex; justify-content:flex-end;">
                <button class="rc-btn rc-btn-outline" onclick="document.getElementById('urlModal').style.display='none'">Close</button>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function () {
            // --- 1. CHARTS ---
            var ctx = document.getElementById('donutChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Answered', 'No Answer', 'Busy'],
                    datasets: [{
                        data: [ {{ $answeredCount }}, {{ $noAnswerCount }}, {{ $busyCount }} ],
                        backgroundColor: ['#10b981', '#ef4444', '#f59e0b'], 
                        borderWidth: 0, spacing: 4, hoverOffset: 6, cutout: '80%' 
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });

            function createSparkline(id, color) {
                var sCtx = document.getElementById(id).getContext('2d');
                new Chart(sCtx, { type: 'line', data: { labels: ['1','2','3','4','5','6','7'], datasets: [{ data: [2, 3, 2, 5, 4, 6, 5], borderColor: color, borderWidth: 2, tension: 0.4, pointRadius: 0 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } }, animation: false } });
            }
            createSparkline('spark1', '#3b82f6'); createSparkline('spark2', '#ef4444'); createSparkline('spark3', '#8b5cf6');

            // --- 2. FILTERING ---
            $('.rc-tab').on('click', function() { $('.rc-tab').removeClass('active'); $(this).addClass('active'); filterTable(); });
            function filterTable() {
                var searchText = $('#rc-search').val().toLowerCase();
                var activeTab = $('.rc-tab.active').data('filter');
                $('.rc-row').each(function() {
                    var row = $(this); var rowText = row.text().toLowerCase(); var rowStatus = row.data('status');
                    var matchesSearch = rowText.indexOf(searchText) > -1;
                    var matchesTab = true;
                    
                    if (activeTab === 'answered' && !['COMPLETED', 'CALL_COMPLETED', 'ANSWERED'].includes(rowStatus)) matchesTab = false;
                    if (activeTab === 'missed' && ['COMPLETED', 'CALL_COMPLETED', 'ANSWERED'].includes(rowStatus)) matchesTab = false;
                    if (activeTab === 'callback' && String(row.data('dtmf')) !== '2') matchesTab = false;
                    
                    if (matchesSearch && matchesTab) { row.show(); } else { row.hide(); }
                });
            }
            $('#rc-search').on('keyup', filterTable); 

            // --- 3. AUTO REFRESH ---
            var refreshInterval = null;
            var isModalOpen = false;

            if (localStorage.getItem('rc_auto_refresh') === 'true') {
                $('#rc-auto-refresh').prop('checked', true); startAutoRefresh();
            }

            $('#rc-auto-refresh').on('change', function() {
                if (this.checked) { localStorage.setItem('rc_auto_refresh', 'true'); startAutoRefresh(); } 
                else { localStorage.setItem('rc_auto_refresh', 'false'); stopAutoRefresh(); }
            });

            function startAutoRefresh() {
                stopAutoRefresh();
                refreshInterval = setInterval(function() {
                    if ($('#rc-search').is(':focus') || isModalOpen) return;
                    // Save scroll position of the dashboard container
                    sessionStorage.setItem('rc_dash_scroll', $('#robocall-dashboard').scrollTop());
                    location.reload();
                }, 15000);
            }

            function stopAutoRefresh() { if (refreshInterval) { clearInterval(refreshInterval); refreshInterval = null; } }

            // Restore dashboard scroll position
            var savedScroll = sessionStorage.getItem('rc_dash_scroll');
            if (savedScroll) {
                $('#robocall-dashboard').scrollTop(parseInt(savedScroll));
                sessionStorage.removeItem('rc_dash_scroll');
            }

            // --- 4. MODAL & COPY ---
            $('#rc-copy').on('click', function () {
                var el = document.getElementById('rc-webhook-url'); el.select();
                try { navigator.clipboard.writeText(el.value); } catch (e) { document.execCommand('copy'); }
                $(this).text('Copied!'); setTimeout(() => $(this).text('Copy'), 1500);
            });

            $('#urlModal').on('click', function(e) { if(e.target === this) { $(this).css('display', 'none'); isModalOpen = false; } });
            $('button[onclick*="urlModal"]').on('click', function() { isModalOpen = true; });

            $('#rc-test').on('click', function () {
                var btn = $(this); btn.prop('disabled', true).html('Sending...');
                $.ajax({
                    type: 'POST', url: $('#rc-webhook-url').val(), contentType: 'application/json', dataType: 'json',
                    data: JSON.stringify({ event: 'call_completed', call_id: 'TEST-' + Date.now(), from: '+920000000000', to: '+923001234567', duration: 45, dtmf: '1', order_id: '99', order_number: 'LAHORE', amount: 1000, recording_url: 'https://example.com/test-recording.mp3' })
                }).always(function () { setTimeout(function () { location.reload(); }, 400); });
            });
        });
    </script>
    @include('Frontend.Layouts.partials.app')
@stop

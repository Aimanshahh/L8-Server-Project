@extends('Frontend.Layouts.default')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* 🚀 PREMIUM ENTERPRISE UI 🚀 */
    .pn-wrapper {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
        min-height: 100vh;
        padding: 40px 20px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }
    .pn-container {
        display: flex;
        gap: 32px;
        max-width: 1000px;
        width: 100%;
    }
    
    /* Header */
    .pn-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    .pn-title {
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.02em;
    }
    .pn-subtitle {
        color: #64748b;
        font-size: 14px;
        margin-top: 4px;
    }

    /* Main Card */
    .pn-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.025);
        padding: 32px;
        flex: 1;
    }
    
    /* Form Elements */
    .pn-form-group {
        margin-bottom: 24px;
        position: relative;
    }
    .pn-label {
        display: block;
        font-weight: 600;
        color: #1e293b;
        font-size: 13px;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .pn-input-icon {
        position: absolute;
        left: 16px;
        top: 42px;
        color: #94a3b8;
        font-size: 16px;
        transition: color 0.2s;
    }
    .pn-textarea-icon {
        top: 44px;
    }
    .pn-input {
        width: 100%;
        padding: 14px 16px 14px 44px; /* Leave space for icon */
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 15px;
        color: #0f172a;
        background: #f8fafc;
        outline: none;
        transition: all 0.2s;
        font-family: 'Inter', sans-serif;
    }
    .pn-input:focus {
        border-color: #3b82f6;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
    }
    .pn-input:focus + .pn-input-icon {
        color: #3b82f6;
    }
    .pn-input::placeholder {
        color: #94a3b8;
    }
    
    /* Audience Selector */
    .pn-select {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 15px;
        color: #0f172a;
        background: #f8fafc;
        outline: none;
        appearance: none;
        cursor: pointer;
    }
    .pn-select:focus {
        border-color: #3b82f6;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
    }
    
    /* Button */
    .pn-btn {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        padding: 16px 24px;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        width: 100%;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }
    .pn-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
    }
    
    /* Alerts */
    .pn-alert {
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-weight: 500;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .pn-alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #10b981; }
    .pn-alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #ef4444; }

    /* 📱 Phone Preview Column 📱 */
    .pn-preview-column {
        flex: 0 0 380px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    /* Realistic Phone Mockup */
    .pn-phone {
        width: 320px;
        height: 650px;
        background: #000;
        border-radius: 48px;
        border: 12px solid #1e293b;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25), inset 0 0 0 2px #334155;
        position: relative;
        overflow: hidden;
        /* Beautiful gradient wallpaper */
        background-image: url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=2564&auto=format&fit=crop'); 
        background-size: cover;
        background-position: center;
    }
    
    /* Dynamic Island / Notch */
    .pn-notch {
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        width: 110px;
        height: 30px;
        background: #000;
        border-radius: 20px;
        z-index: 10;
    }
    
    /* Status Bar */
    .pn-status-bar {
        position: absolute;
        top: 15px;
        left: 24px;
        right: 24px;
        display: flex;
        justify-content: space-between;
        color: white;
        font-size: 12px;
        font-weight: 600;
        z-index: 5;
    }

    /* Notification Card */
    .pn-notification {
        position: absolute;
        top: 60px;
        left: 12px;
        right: 12px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .pn-notif-top {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .pn-app-icon {
        width: 24px;
        height: 24px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border-radius: 6px;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 12px;
    }
    .pn-app-name {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        flex-grow: 1;
    }
    .pn-time {
        font-size: 11px;
        color: #94a3b8;
    }
    .pn-notif-title {
        font-weight: 700;
        font-size: 15px;
        color: #0f172a;
        margin: 0;
    }
    .pn-notif-body {
        font-size: 14px;
        color: #475569;
        margin: 0;
        line-height: 1.4;
    }

    /* Home Indicator */
    .pn-home-indicator {
        position: absolute;
        bottom: 8px;
        left: 50%;
        transform: translateX(-50%);
        width: 120px;
        height: 5px;
        background: rgba(255,255,255,0.5);
        border-radius: 10px;
    }
</style>

<div class="pn-wrapper">
    <div class="pn-container">
        
        <!-- Left: Form -->
        <div class="pn-card">
            <div class="pn-header">
                <div>
                    <h3 class="pn-title">Push Notification</h3>
                    <p class="pn-subtitle">Send a direct message to your mobile app users.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="pn-alert pn-alert-success">
                    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="pn-alert pn-alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('push.send') }}">
                @csrf
                
                <div class="pn-form-group">
                    <label class="pn-label">Target Audience</label>
                    <select class="pn-select" name="audience">
                        <option value="all">All Devices</option>
                        <option value="android">Android Only</option>
                        <option value="ios" disabled>iOS (Coming Soon)</option>
                    </select>
                </div>

                <div class="pn-form-group">
                    <label class="pn-label">Notification Title</label>
                    <i class="fa-regular fa-bell pn-input-icon"></i>
                    <input type="text" name="title" id="input-title" required class="pn-input" placeholder="e.g., New Offer Available!" autocomplete="off">
                </div>

                <div class="pn-form-group">
                    <label class="pn-label">Message Body</label>
                    <i class="fa-regular fa-message pn-input-icon pn-textarea-icon"></i>
                    <textarea name="body" id="input-body" required rows="4" class="pn-input" placeholder="Type your message here..."></textarea>
                </div>

                <button type="submit" class="pn-btn">
                    Send Notification <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>

        <!-- Right: Live Phone Preview -->
        <div class="pn-preview-column">
            <div class="pn-phone">
                <div class="pn-notch"></div>
                
                <div class="pn-status-bar">
                    <span>9:41</span>
                    <span><i class="fa-solid fa-signal"></i> <i class="fa-solid fa-wifi"></i> <i class="fa-solid fa-battery-full"></i></span>
                </div>

                <div class="pn-notification">
                    <div class="pn-notif-top">
                        <div class="pn-app-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <span class="pn-app-name">GPS Tracker</span>
                        <span class="pn-time">now</span>
                    </div>
                    <h4 class="pn-notif-title" id="preview-title">Notification Title</h4>
                    <p class="pn-notif-body" id="preview-body">Your message will appear here...</p>
                </div>

                <div class="pn-home-indicator"></div>
            </div>
        </div>

    </div>
</div>

<script>
    // Live Preview Logic
    document.getElementById('input-title').addEventListener('input', function() {
        let val = this.value;
        document.getElementById('preview-title').innerText = val ? val : 'Notification Title';
    });

    document.getElementById('input-body').addEventListener('input', function() {
        let val = this.value;
        document.getElementById('preview-body').innerText = val ? val : 'Your message will appear here...';
    });
</script>
@stop
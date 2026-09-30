<title>{{ Appearance::getSetting('server_name') }}</title>

<base href="{{ url('/') }}">
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="app-version" content="{{ config('tobuli.version') }}">
<meta name="app-build" content="{{ config('app.build') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="{{ Appearance::getSetting('server_description') }}">
<link rel="shortcut icon" href="{{ Appearance::getAssetFileUrl('favicon') }}" type="image/x-icon">
<link rel="stylesheet" href="{{ asset_resource(theme_base_css()) }}">
<link rel="stylesheet" href="{{ asset_resource('assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset_resource('assets/css/dashboard-overrides.css') }}?v=20260907-3">
<link rel="stylesheet" href="{{ asset_resource('assets/css/sidebar-overrides.css') }}?v=20260907-1">    <link rel="stylesheet" href="{{ asset_resource('assets/css/map-sidebar-overrides.css') }}?v=20260907-1">
    <link rel="stylesheet" href="{{ asset_resource('assets/css/modal-overrides.css') }}?v=20260918-1">
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" charset="utf-8"></script>
<script src="{{ asset_resource('assets/js/custom.js') }}" type="text/javascript"></script>
@if (Language::dir() == 'rtl')
    <link rel="stylesheet" href="{{ asset_resource('assets/css/rtl.css') }}">
@endif
@if (Appearance::assetFileExists('css'))
    <link rel="stylesheet" href="{{ Appearance::getAssetFileUrl('css') }}">
@endif
@if (Appearance::assetFileExists('js'))
    <script src="{{ Appearance::getAssetFileUrl('js') }}" type="text/javascript" defer></script>
@endif
<link rel="stylesheet" href="{{ asset_resource('assets/css/objects-page-overrides.css') }}?v=20260930-1">
<link rel="stylesheet" href="{{ asset_resource('assets/css/theme-dark.css') }}?v=20260930-1">
@include('Frontend.Layouts.partials.theme-script')

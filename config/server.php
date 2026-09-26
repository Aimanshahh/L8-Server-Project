<?php
return [
    'device_limit' => env('limit'),
    'floating_ip' => env('FLOATING_IP'),
    'device_memory_limit' => env('OBJECT_MEMORY_LIMIT', '512M'),
    'report_memory_limit' => env('REPORT_MEMORY_LIMIT', '2048M'),
    'api_login_throttle' => env('API_LOGIN_THROTTLE_COUNT', '60'),
    'login_redirect_route' => env('LOGIN_REDIRECT_ROUTE', null),
    'entity_loader_page_limit' => env('ENTITY_LOADER_PAGE_LIMIT', 100),
];
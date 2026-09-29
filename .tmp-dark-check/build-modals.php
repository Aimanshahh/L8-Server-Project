<?php
// Temporary visual harness: renders real modal fragments through the app and
// wraps them in the real stylesheet chain so both themes can be compared.
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->instance('request', Illuminate\Http\Request::create('/', 'GET'));
$kernel->bootstrap();

$user = Tobuli\Entities\User::find(1);
$app['auth']->guard('web')->setUser($user);

$targets = [
    'client-create' => '/admin/clients/create',
    'device-create' => '/devices/create',
    'client-destroy' => '/admin/clients/destroy/2',
];

$fragments = [];
foreach ($targets as $key => $uri) {
    $request = Illuminate\Http\Request::create($uri, 'GET');
    $app->instance('request', $request);
    try {
        $response = $kernel->handle($request);
        $body = $response->getContent();
        $fragments[$key] = substr($body, 0, 200) === '<!DOCTYPE html>' ? '<!-- layout response, skipped -->' : $body;
    } catch (\Throwable $e) {
        $fragments[$key] = '<!-- ' . get_class($e) . ': ' . $e->getMessage() . ' -->';
    }
}

$css = [
    '../public/assets/css/light-blue.css',
    '../public/assets/css/style.css',
    '../public/assets/css/sidebar-overrides.css',
    '../public/assets/css/modal-overrides.css',
    '../public/assets/css/admin-list-overrides.css',
    '../public/assets/css/admin-users-overrides.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    '../public/assets/css/theme-dark.css',
];

$links = '';
foreach ($css as $href) {
    $links .= '<link rel="stylesheet" href="' . $href . '">' . "\n";
}

$panels = '';
foreach ($fragments as $key => $html) {
    $panels .= '<section class="panel"><h2>' . htmlspecialchars($key) . '</h2>' . $html . '</section>' . "\n";
}

$page = <<<HTML
<!doctype html>
<html lang="en" data-theme="dark">
<script>
    (function () {
        var m = /(?:^|\?|&)theme=(light|dark)(?:&|\$)/.exec(location.search);
        if (m) document.documentElement.setAttribute('data-theme', m[1]);
    })();
</script>
<head>
<meta charset="utf-8">
<title>Modal theme check</title>
$links
<style>
    html, body { margin: 0; padding: 0; }
    body { font-family: Inter, Arial, sans-serif; padding: 24px; }
    .panel { margin: 0 0 32px; }
    .panel > h2 { font-size: 13px; letter-spacing: .08em; text-transform: uppercase; opacity: .6; }
    .modal { position: static; display: block; background: transparent; }
    .modal .modal-dialog { margin: 0; width: 100%; max-width: 640px; }
</style>
</head>
<body>
$panels
</body>
</html>
HTML;

file_put_contents(__DIR__ . '/modal.html', $page);
echo "wrote modal.html\n";
foreach ($fragments as $key => $html) {
    echo $key . ': ' . strlen($html) . " bytes\n";
}

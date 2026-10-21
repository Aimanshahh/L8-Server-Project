<?php
/**
 * Wiring checks for the robocall feature that do not need a DB.
 *   php tmp_robocall_wiring_test.php
 */

require __DIR__ . '/vendor/autoload.php';

$fail = 0;
$ok = 0;

function check($label, $cond, $extra = '')
{
    global $fail, $ok;

    if ($cond) { $ok++; echo "  PASS  {$label}\n"; }
    else       { $fail++; echo "  FAIL  {$label} {$extra}\n"; }
}

echo "1. routes/web.php\n";
$routes = file_get_contents(__DIR__ . '/routes/web.php');

$expect = [
    'robocall.webhook'  => 'webhook',
    'robocall.index'    => 'index',
    'robocall.data'     => 'data',
    'robocall.download' => 'download',
    'robocall.clear'    => 'clear',
];

foreach ($expect as $name => $method) {
    check("route {$name} registered", substr_count($routes, "'as' => '{$name}'") === 1);
}

check('webhook route is outside auth middleware (public)',
    (bool) preg_match("/gpsdata_insert[^\n]*\n\n    # Public robocall receiver.*?'robocall\/webhook'/s", $routes));

check('webhook route uses RobocallController@webhook',
    strpos($routes, "RobocallController@webhook") !== false);

// Every RobocallController@method referenced in routes must exist.
preg_match_all('/RobocallController@(\w+)/', $routes, $m);
$referenced = array_unique($m[1]);

echo "2. controller methods\n";
$reflection = new ReflectionClass(\App\Http\Controllers\Frontend\RobocallController::class);
$parent = $reflection->getParentClass();

check('extends App\Http\Controllers\Controller',
    $parent && $parent->getName() === 'App\Http\Controllers\Controller', $parent ? $parent->getName() : 'none');

foreach ($referenced as $method) {
    check("method {$method}() exists and is public",
        $reflection->hasMethod($method) && $reflection->getMethod($method)->isPublic());
}

check('all 5 route methods referenced', count($referenced) === 5, json_encode($referenced));

echo "3. CSRF exemption\n";
$csrf = file_get_contents(__DIR__ . '/app/Http/Middleware/VerifyCsrfToken.php');
check("'robocall/webhook' is CSRF exempt", strpos($csrf, "'robocall/webhook'") !== false);

echo "4. sidebar entry\n";
$header = file_get_contents(__DIR__ . '/Tobuli/Views/Frontend/Layouts/partials/header.blade.php');
check('objects-page rail links to robocall.index', strpos($header, "route('robocall.index')") !== false);

$dashboard = file_get_contents(__DIR__ . '/Tobuli/Views/Frontend/Dashboard/index.blade.php');
check('dashboard rail links to robocall.index', strpos($dashboard, "route('robocall.index')") !== false);

// The rail icon class must actually exist in the stylesheet, otherwise the
// entry renders an empty box.
$css = file_get_contents(__DIR__ . '/public/assets/css/facelift.css');
foreach (['header' => $header, 'dashboard' => $dashboard] as $where => $html) {
    preg_match('/href="[^"]*?\.*robocall[^"]*?"[^>]*>.{0,120}?class="icon ([a-z_-]+)"/s', $html, $im);
    $icon = $im[1] ?? '';
    check("{$where} rail icon '{$icon}' is defined in CSS",
        $icon !== '' && strpos($css, ".icon.{$icon}:before") !== false);
}

echo "5. blade template compiles\n";
$view = __DIR__ . '/Tobuli/Views/Frontend/Robocall/index.blade.php';
check('view file exists', file_exists($view));

$cache = sys_get_temp_dir() . '/rc_blade_' . getmypid();
@mkdir($cache, 0777, true);

$compiler = new Illuminate\View\Compilers\BladeCompiler(new Illuminate\Filesystem\Filesystem(), $cache);
$compiledPath = $compiler->getCompiledPath($view);
$compiler->compile($view);

check('compiled file written', file_exists($compiledPath));

$compiled = file_get_contents($compiledPath);
check('compiled output is non-empty', strlen($compiled) > 1000, strlen($compiled) . ' bytes');

// The compiled PHP must itself be valid and must not reference a stale constant.
$tmpPhp = $cache . '/compiled_test.php';
file_put_contents($tmpPhp, $compiled);

exec('php -l ' . escapeshellarg($tmpPhp) . ' 2>&1', $lintOut, $lintCode);
check('compiled blade is valid PHP', $lintCode === 0, implode(' | ', $lintOut));

check('no reference to removed controller constant',
    strpos($compiled, 'RobocallController::PAGE_ROWS') === false);
check('view guards on $storageWritable', strpos($compiled, '$storageWritable') !== false);
check('view renders the not-writable warning', strpos($compiled, 'Storage is not writable') !== false);

$ctrl = file_get_contents(__DIR__ . '/app/Http/Controllers/Frontend/RobocallController.php');
check('controller answers 500 when the payload cannot be stored',
    strpos($ctrl, '500') !== false && strpos($ctrl, "'stored'  => false") !== false);
check('controller logs the failed write', strpos($ctrl, 'Log::error') !== false);

// Every route name / helper the template calls must be one we registered.
preg_match_all("/route\('([\w.]+)'/", $compiled, $rm);
$called = array_unique($rm[1]);
$known = ['robocall.webhook', 'robocall.download', 'robocall.clear', 'robocall.index', 'objects.index', 'admin', 'my_account_settings.edit'];
foreach ($called as $name) {
    check("template route('{$name}') is valid", in_array($name, $known, true));
}

// Variables the compiled view expects, against what index() passes.
preg_match_all('/\$([a-zA-Z_]\w*)/', $compiled, $vm);
$used = array_unique($vm[1]);
$ignored = ['pageRows'];
$passedController = file_get_contents(__DIR__ . '/app/Http/Controllers/Frontend/RobocallController.php');
foreach (['webhookUrl', 'calls', 'total', 'storagePath', 'pageRows'] as $var) {
    check("index() passes \${$var}", strpos($passedController, "'{$var}'") !== false);
}

array_map('unlink', glob($cache . '/*') ?: []);
@rmdir($cache);

echo "\n" . ($fail === 0 ? "ALL {$ok} CHECKS PASSED" : "{$fail} FAILED / {$ok} passed") . "\n";
exit($fail === 0 ? 0 : 1);

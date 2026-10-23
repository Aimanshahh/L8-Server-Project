<?php

$result = [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */

    'name' => 'gpswox server',
    'build' => '202406260854178174',
    'server' => env('server', 'localhost'),
    'admin_user' => env('admin_user', 'admin'),

    'force_https' => env('FORCE_HTTPS', false),

    'trust_hosts' => array_filter(explode(';', env('TRUST_HOSTS', null))),

	/*
	|--------------------------------------------------------------------------
	| Application Debug Mode
	|--------------------------------------------------------------------------
	*/

	'debug' => env('APP_DEBUG', false),

    'debug_blacklist' => [
        '_ENV' => array_keys($_ENV),
        '_SERVER' => array_keys($_ENV)
    ],

	/*
	|--------------------------------------------------------------------------
	| Application URL
	|--------------------------------------------------------------------------
	*/

	'url' => env('APP_URL', 'http://localhost'),

	/*
	|--------------------------------------------------------------------------
	| Application Timezone
	|--------------------------------------------------------------------------
	*/

	'timezone' => 'UTC',

	/*
	|--------------------------------------------------------------------------
	| Application Locale Configuration
	|--------------------------------------------------------------------------
	*/

	'locale' => 'en',

	/*
	|--------------------------------------------------------------------------
	| Application Fallback Locale
	|--------------------------------------------------------------------------
	*/

	'fallback_locale' => 'en',

	/*
	|--------------------------------------------------------------------------
	| Encryption Key
	|--------------------------------------------------------------------------
	*/

	'key' => env('APP_KEY', 'SomeRandomString'),

	'cipher' => 'AES-256-CBC', //@TODO: must regenerate key using key:generate command

	/*
	|--------------------------------------------------------------------------
	| Environment
	|--------------------------------------------------------------------------
	*/
	'env' => env('APP_ENV', 'production'),

	/*
	|--------------------------------------------------------------------------
	| Autoloaded Service Providers
	|--------------------------------------------------------------------------
	*/

	'providers' => [

		/*
		 * Laravel Framework Service Providers...
		 */
		'Illuminate\Auth\AuthServiceProvider',
		'Illuminate\Bus\BusServiceProvider',
		'Illuminate\Cache\CacheServiceProvider',
		'Illuminate\Foundation\Providers\ConsoleSupportServiceProvider',
		'Illuminate\Cookie\CookieServiceProvider',
		'Illuminate\Database\DatabaseServiceProvider',
		'Illuminate\Encryption\EncryptionServiceProvider',
		'Illuminate\Filesystem\FilesystemServiceProvider',
		'Illuminate\Foundation\Providers\FoundationServiceProvider',
		'Illuminate\Hashing\HashServiceProvider',
		//'Illuminate\Mail\MailServiceProvider',
		'Illuminate\Pagination\PaginationServiceProvider',
		'Illuminate\Pipeline\PipelineServiceProvider',
		'Illuminate\Queue\QueueServiceProvider',
		'Illuminate\Redis\RedisServiceProvider',
		'Illuminate\Auth\Passwords\PasswordResetServiceProvider',
		'Illuminate\Session\SessionServiceProvider',
		'Illuminate\Translation\TranslationServiceProvider',
		'Illuminate\Validation\ValidationServiceProvider',
		'Illuminate\View\ViewServiceProvider',
		//'Illuminate\Html\HtmlServiceProvider',
        'Collective\Html\HtmlServiceProvider',
        'App\Providers\ConfigurableMailServiceProvider',
		'Maatwebsite\Excel\ExcelServiceProvider',
		'Bugsnag\BugsnagLaravel\BugsnagServiceProvider',
		'Illuminate\Broadcasting\BroadcastServiceProvider',
        'Barryvdh\DomPDF\ServiceProvider',
        //'App\Providers\HTMLMinifyServiceProvider',
        'Barryvdh\Snappy\ServiceProvider',
        'SimpleSoftwareIO\QrCode\QrCodeServiceProvider',
        'Yajra\DataTables\DatatablesServiceProvider',

		/*
		 * Application Service Providers...
		 */
		'App\Providers\AppServiceProvider',
        'App\Providers\AuthServiceProvider',
		'App\Providers\EventServiceProvider',
		'App\Providers\RouteServiceProvider',
        'App\Providers\SettingsServiceProvider',
        'App\Providers\ValidatorRulesServiceProvider',
		'Tobuli\Repositories\RepositoriesServiceProvider',
		'Fideloper\Proxy\TrustedProxyServiceProvider',
        'Illuminate\Notifications\NotificationServiceProvider',
        'App\Providers\FractalTransformerServiceProvider',
        'App\Providers\PropertyPolicyServiceProvider',
        'App\Providers\ModelLogConfigProvider',
        'App\Providers\FileSystemMacroProvider',
        'App\Providers\CollectionMacroProvider',
        'App\Providers\CacheServiceProvider',
        'App\Providers\FormatterServiceProvider',
        'App\Providers\TranslationServiceProvider',
        'App\Providers\FtpUserServiceProvider',
        'App\Providers\SharingServiceProvider',
        'App\Providers\ChecklistServiceProvider',
        'App\Providers\DeviceConfigServiceProvider',
        'App\Providers\DeviceConfigUpdateServiceProvider',
        'App\Providers\ActionPolicyManagerProvider',
        'App\Providers\LanguageServiceProvider',
        'App\Providers\MorphMapServiceProvider',
        'App\Providers\CustomValuesServiceProvider',
        'App\Providers\SimBlockingServiceProvider',
        'Mews\Captcha\CaptchaServiceProvider',
        'App\Providers\CaptchaServiceProvider',
        'App\Providers\AppearanceServiceProvider',
        'App\Providers\QueryBuilderMacrosProvider',
        
        
        \App\Providers\AuthManagerProvider::class,
    ],

	/*
	|--------------------------------------------------------------------------
	| Class Aliases
	|--------------------------------------------------------------------------
	*/

	'aliases' => [

		'App'       => 'Illuminate\Support\Facades\App',
        'Arr'       => Illuminate\Support\Arr::class,
		'Artisan'   => 'Illuminate\Support\Facades\Artisan',
		'Auth'      => 'Illuminate\Support\Facades\Auth',
		'Blade'     => 'Illuminate\Support\Facades\Blade',
		'Bus'       => 'Illuminate\Support\Facades\Bus',
		'Cache'     => 'Illuminate\Support\Facades\Cache',
		'Config'    => 'Illuminate\Support\Facades\Config',
		'Cookie'    => 'Illuminate\Support\Facades\Cookie',
		'Crypt'     => 'Illuminate\Support\Facades\Crypt',
		'DB'        => 'Illuminate\Support\Facades\DB',
		'Eloquent'  => 'Illuminate\Database\Eloquent\Model',
		'Event'     => 'Illuminate\Support\Facades\Event',
		'File'      => 'Illuminate\Support\Facades\File',
        'Gate'      => Illuminate\Support\Facades\Gate::class,
		'Hash'      => 'Illuminate\Support\Facades\Hash',
        'Http'      => Illuminate\Support\Facades\Http::class,
		'Inspiring' => 'Illuminate\Foundation\Inspiring',
		'Lang'      => 'Illuminate\Support\Facades\Lang',
		'Log'       => 'Illuminate\Support\Facades\Log',
		'Mail'      => 'Illuminate\Support\Facades\Mail',
		'Password'  => 'Illuminate\Support\Facades\Password',
		'Queue'     => 'Illuminate\Support\Facades\Queue',
		'Redirect'  => 'Illuminate\Support\Facades\Redirect',
		'Redis'     => 'Illuminate\Support\Facades\Redis',
		'Request'   => 'Illuminate\Support\Facades\Request',
		'Response'  => 'Illuminate\Support\Facades\Response',
		'Route'     => 'Illuminate\Support\Facades\Route',
		'Schema'    => 'Illuminate\Support\Facades\Schema',
		'Session'   => 'Illuminate\Support\Facades\Session',
		'Storage'   => 'Illuminate\Support\Facades\Storage',
        'Str'       => Illuminate\Support\Str::class,
		'URL'       => 'Illuminate\Support\Facades\URL',
		'Validator' => 'Illuminate\Support\Facades\Validator',
		'View'      => 'Illuminate\Support\Facades\View',

        'Carbon'    => 'Carbon\Carbon',
		'Form'      => 'Collective\Html\FormFacade',
		'HTML'      => 'Collective\Html\HtmlFacade',
		'Excel'     => 'Maatwebsite\Excel\Facades\Excel',
		'PDF'       => 'Barryvdh\DomPDF\Facade',
		'Bugsnag'   => 'Bugsnag\BugsnagLaravel\Facades\Bugsnag',
        'Settings'  => 'CustomFacades\Settings',
        'FractalTransformer' => 'CustomFacades\FractalTransformerServiceFacade',
        'Formatter' => 'Tobuli\Helpers\Formatter\Facades\Formatter',
        'QrCode'    => 'SimpleSoftwareIO\QrCode\Facades\QrCode',
        'Language'  => 'CustomFacades\Language',
        'Datatables' => 'Yajra\DataTables\Facades\DataTables',
        'Notification' => 'Illuminate\Support\Facades\Notification',
        'Appearance'  => 'CustomFacades\Appearance',
        'Field' => 'CustomFacades\Field',
	],
];

if (env('APP_ENV') === 'local')
{
    $result['providers'][] = 'Barryvdh\Debugbar\ServiceProvider';
    $result['providers'][] = 'Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider';

    $result['aliases']['Debugbar'] = 'Barryvdh\Debugbar\Facade';

}

return $result;
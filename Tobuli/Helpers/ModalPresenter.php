<?php

namespace Tobuli\Helpers;

/**
 * Presentation helper for the shared dialog chrome.
 *
 * Every dialog in the application is rendered through one of the three shared
 * modal layouts (Admin.Layouts.modal, Frontend.Layouts.modal and
 * Frontend.Layouts.modal_full), and is fetched over AJAX from its own URL. That
 * makes it possible to offer a sensible header icon, subtitle and title to all
 * of them from one place, without editing each of the ~220 dialog views.
 *
 * A dialog always wins over anything derived here: if a view defines its own
 * @section('icon'), @section('subtitle') or a descriptive @section('title'),
 * that value is used untouched.
 *
 * Nothing here reads or writes application data - it only inspects the current
 * request path to decide how the header should read.
 */
class ModalPresenter
{
    /**
     * Route module => [header glyph (Font Awesome 6), singular human noun].
     */
    protected static $modules = [
        // administration / configuration
        'email_templates'       => ['fa-envelope', 'email template'],
        'sms_templates'         => ['fa-comment-sms', 'SMS template'],
        'command_templates'     => ['fa-terminal', 'command template'],
        'device_expenses_types' => ['fa-receipt', 'expense type'],
        'device_config'         => ['fa-microchip', 'GPS device configuration'],
        'apn_config'            => ['fa-network-wired', 'APN configuration'],
        'diem_rates'            => ['fa-money-bill-wave', 'diem rate'],
        'device_icons'          => ['fa-car-side', 'device icon'],
        'device_types'          => ['fa-tags', 'device type'],
        'device_type_imei'      => ['fa-sim-card', 'IMEI rule'],
        'device_plans'          => ['fa-file-invoice', 'device plan'],
        'custom_fields'         => ['fa-rectangle-list', 'custom field'],
        'map_icons'             => ['fa-map-location-dot', 'map icon'],
        'sensor_icons'          => ['fa-microchip', 'sensor icon'],
        'pages'                 => ['fa-file-lines', 'page'],
        'companies'             => ['fa-building', 'company'],
        'email_settings'        => ['fa-envelope-open-text', 'email setting'],
        'main_server_settings'  => ['fa-server', 'server setting'],
        'settings'              => ['fa-gear', 'setting'],
        'backups'               => ['fa-database', 'backup'],
        'backup'                => ['fa-database', 'backup'],
        'permissions'           => ['fa-key', 'permission'],
        'roles'                 => ['fa-user-shield', 'role'],
        'webhooks'              => ['fa-bolt', 'webhook'],
        'languages'             => ['fa-language', 'language'],
        'currencies'            => ['fa-coins', 'currency'],
        'units'                 => ['fa-ruler', 'unit'],
        'templates'             => ['fa-clone', 'template'],
        'widgets'               => ['fa-table-cells-large', 'widget'],

        // accounts
        'clients'               => ['fa-users', 'client'],
        'users'                 => ['fa-user-gear', 'user'],
        'admins'                => ['fa-user-tie', 'administrator'],
        'managers'              => ['fa-user-tie', 'manager'],
        'subusers'              => ['fa-user-group', 'subuser'],
        'drivers'               => ['fa-id-card', 'driver'],
        'account'               => ['fa-user-gear', 'account'],
        'profile'               => ['fa-id-card', 'profile'],
        'groups'                => ['fa-layer-group', 'group'],
        'billing'               => ['fa-file-invoice-dollar', 'billing detail'],
        'subscriptions'         => ['fa-file-invoice-dollar', 'subscription'],
        'payments'              => ['fa-credit-card', 'payment'],

        // tracking
        'objects'               => ['fa-car', 'object'],
        'devices'               => ['fa-car', 'device'],
        'device'                => ['fa-car', 'device'],
        'sensors'               => ['fa-gauge-high', 'sensor'],
        'geofences'             => ['fa-draw-polygon', 'geofence'],
        'pois'                  => ['fa-map-pin', 'place'],
        'routes'                => ['fa-route', 'route'],
        'trailers'              => ['fa-truck-moving', 'trailer'],
        'commands'              => ['fa-terminal', 'command'],
        'tasks'                 => ['fa-clipboard-check', 'task'],
        'maintenances'          => ['fa-screwdriver-wrench', 'maintenance record'],
        'maintenance'           => ['fa-screwdriver-wrench', 'maintenance record'],
        'expenses'              => ['fa-receipt', 'expense'],
        'alerts'                => ['fa-bell', 'alert'],
        'events'                => ['fa-list-ul', 'event'],
        'history'               => ['fa-clock-rotate-left', 'history record'],
        'reports'               => ['fa-chart-line', 'report'],
        'logs'                  => ['fa-file-lines', 'log'],
        'media'                 => ['fa-camera', 'media file'],
        'device_media'          => ['fa-photo-film', 'media file'],
        'media_categories'      => ['fa-folder', 'media category'],
        'device_camera'         => ['fa-video', 'camera'],
        'services'              => ['fa-screwdriver-wrench', 'service'],
        'secondary_credentials' => ['fa-key', 'credential'],
        'email_confirmation'    => ['fa-envelope-circle-check', 'email confirmation'],
        'send_command'          => ['fa-terminal', 'command'],
        'my_account'            => ['fa-user-gear', 'account'],
        'my_account_settings'   => ['fa-gear', 'account setting'],
        'lookup'                => ['fa-list', 'list'],
        'documents'             => ['fa-folder-open', 'document'],
        'notes'                 => ['fa-note-sticky', 'note'],
        'chat'                  => ['fa-comments', 'message'],
        'icons'                 => ['fa-icons', 'icon'],
    ];

    /**
     * Path segments that describe an action rather than a module.
     */
    protected static $verbs = [
        'admin', 'create', 'edit', 'store', 'update', 'destroy', 'show', 'index',
        'table', 'delete', 'add', 'new', 'form', 'modal', 'import', 'export',
        'upload', 'mass', 'csv', 'get', 'list', 'view', 'send', 'test',
    ];

    /**
     * Titles that carry no information of their own - these are enriched with
     * the module noun ("Edit" becomes "Edit Email Template").
     */
    protected static $bareTitles = [
        'add', 'edit', 'create', 'new', 'update', 'save', 'form', 'delete',
        'remove', 'view', 'details',
    ];

    /**
     * Enrich a dialog title. Anything the view wrote itself is preserved unless
     * it is a bare verb, in which case the module noun is appended.
     *
     * @param  string|null  $current
     * @return string
     */
    public static function title($current)
    {
        $current = (string) $current;
        $plain = trim(strip_tags($current));

        if ($plain === '' || !in_array(mb_strtolower($plain), static::$bareTitles, true)) {
            return $current;
        }

        $module = static::module();

        if (!$module) {
            return $current;
        }

        $action = static::action();

        if ($action === 'create') {
            return 'Add ' . static::headline($module[1]);
        }

        if ($action === 'edit') {
            return 'Edit ' . static::headline($module[1]);
        }

        return $current;
    }

    /**
     * Header glyph class (or markup) for the current dialog, null when nothing
     * sensible could be derived.
     *
     * @return string|null
     */
    public static function icon()
    {
        $module = static::module();

        if ($module) {
            return '<i class="fas ' . $module[0] . '"></i>';
        }

        switch (static::action()) {
            case 'create':
                return '<i class="fas fa-plus"></i>';
            case 'edit':
                return '<i class="fas fa-pen-to-square"></i>';
        }

        return null;
    }

    /**
     * Short explanation line under the dialog title, null when the dialog is
     * not an obvious create/edit form.
     *
     * @return string|null
     */
    public static function subtitle()
    {
        $action = static::action();
        $module = static::module();
        $noun = $module ? $module[1] : null;

        if ($action === 'create') {
            return $noun
                ? 'Add a new ' . $noun . ' and save it.'
                : 'Fill in the details below to add a new entry.';
        }

        if ($action === 'edit') {
            return $noun
                ? 'Update the ' . $noun . ' details and save your changes.'
                : 'Update the details below and save your changes.';
        }

        return null;
    }

    /**
     * The module this dialog belongs to.
     *
     * The most specific (last) recognised segment wins, so nested routes such as
     * /devices/12/sensors/create resolve to the sensor dialog.
     *
     * @return array|null  [icon, noun]
     */
    protected static function module()
    {
        foreach (array_reverse(static::segments()) as $segment) {
            if (isset(static::$modules[$segment])) {
                return static::$modules[$segment];
            }
        }

        return null;
    }

    /**
     * create / edit / null, taken from the request path.
     *
     * @return string|null
     */
    protected static function action()
    {
        $path = '/' . trim(static::path(), '/') . '/';

        if (preg_match('~/create/~', $path) || preg_match('~/add/~', $path)) {
            return 'create';
        }

        if (preg_match('~/edit/~', $path)) {
            return 'edit';
        }

        return null;
    }

    /**
     * Meaningful path segments, normalised and with action words removed.
     *
     * @return array
     */
    protected static function segments()
    {
        $segments = [];

        foreach (explode('/', static::path()) as $segment) {
            $segment = strtolower(trim($segment));

            if ($segment === '' || is_numeric($segment)) {
                continue;
            }

            $segment = str_replace(['-', '.'], '_', $segment);

            if (in_array($segment, static::$verbs, true)) {
                continue;
            }

            $segments[] = $segment;
        }

        return $segments;
    }

    /**
     * Current request path, safe to call from any context.
     *
     * @return string
     */
    protected static function path()
    {
        try {
            return (string) request()->path();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * "email template" => "Email Template", "APN configuration" => "APN Configuration".
     *
     * ucwords only touches the first letter of each word, so acronyms such as
     * GPS, APN and SMS keep their casing.
     *
     * @param  string  $noun
     * @return string
     */
    protected static function headline($noun)
    {
        return ucwords($noun);
    }
}

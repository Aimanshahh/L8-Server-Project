<?php

use Illuminate\Support\Arr;

function getNavigation($adminOnly = false) {

    $user = Auth::user();

    $stats = $user
        ? Cache::remember($user->id . '_online_cache', 60, function() use ($user) {

            $total_devices = $user
                ->accessibleDevices()
                ->count('devices.id');

            $online_devices = $user
                ->accessibleDevices()
                ->online(config('tobuli.device_offline_minutes'))
                ->count('devices.id');

            $total_users = \Tobuli\Entities\User::userAccessible($user)->count();

            return [
                'total_devices'  => $total_devices,
                'online_devices' => $online_devices,
                'total_users'    => $total_users
            ];
        })
        : [];

    $currentRoute = Route::getCurrentRoute()->getName();
    $childs = [];

    $items = [];

    if ( ! $adminOnly) {
        $items[] = [
            'title' => '<i class="icon map"></i> ' . '<span class="nav__link-txt">' .  trans('admin.map') . '</span>',
            'route' => 'objects.index',
            'childs' => []
        ];
    }

    #Users
    if (Auth::User()->can('view', new \Tobuli\Entities\User())) {
        $items[] = [
            'title' => '<i class="icon users"></i> ' . '<span class="nav__link-txt">' . trans('admin.users') . ' (' . Arr::get($stats, 'total_users', 0) . ')</span>',
            'route' => 'admin.clients.index',
            'childs' => []
        ];
    }

    #Objects
    if (Auth::User()->can('view', new \Tobuli\Entities\Device())) {
        $items[] = [
            'title' => '<i class="icon device"></i> ' . '<span class="nav__link-txt">' . trans('admin.objects') . ' (' . Arr::get($stats, 'online_devices', 0) . '/' . Arr::get($stats, 'total_devices', 0) . ')</span>',
            'route' => 'admin.objects.index',
            'childs' => []
        ];
    }

    $items['content'] = [
        'title' => '<i class="icon content"></i> ' . '<span class="nav__link-txt">' .  trans('admin.content') . '</span>',
        'route' => '',
    ];

    if (Auth::user()->canChangeAppearance()) {
        $items['content'] = [
            'title' => '<i class="icon content"></i> ' . '<span class="nav__link-txt">' .  trans('admin.content') . '</span>',
            'route' => '',
            'childs' => [
                [
                    'title' =>  '<span class="icon mail"></span>' . '<span class="nav__link-txt">' . trans('admin.email_templates') . '</span>',
                    'route' => 'admin.email_templates.index',
                    'childs' => ''
                ],
                [
                    'title' =>  '<span class="icon message"></span>' . '<span class="nav__link-txt">' . trans('front.sms_templates') . '</span>',
                    'route' => 'admin.sms_templates.index',
                    'childs' => ''
                ],
            ]
        ];

        if (config('addon.notification_popops')) {
            $items['content']['childs'][] = [
                'title' =>  '<span class="icon note"></span>' . '<span class="nav__link-txt">' . trans('admin.popups') . '</span>',
                'route' => 'admin.popups.index',
                'childs' => ''
            ];
        }

        $items[] = [
            'title' => '<i class="icon setup"></i> ' . '<span class="nav__link-txt">' .  trans('validation.attributes.logo') . '</span>',
            'route' => 'admin.main_server_settings.index',
            'childs' => []
        ];
        
    }

    $items['content']['childs'][] = [
        'title' => '<span class="icon company"></span>' . '<span class="nav__link-txt">' . trans('admin.companies') . '</span>',
        'route' => 'admin.companies.index',
        'childs' => '',
    ];

    if (Auth::user()->isAdmin()) {
        $items[] = [
            'title' => '<i class="icon events"></i> ' . '<span class="nav__link-txt">' .  trans('admin.events') . '</span>',
            'route' => 'admin.events.index',
            'childs' => []
        ];

        $items['content'] = [
            'title' => '<i class="icon content"></i> ' . '<span class="nav__link-txt">' .  trans('admin.content') . '</span>',
            'route' => '',
            'childs' => [
                [
                    'title' =>  '<span class="icon mail"></span>' . '<span class="nav__link-txt">' . trans('admin.email_templates') . '</span>',
                    'route' => 'admin.email_templates.index',
                    'childs' => ''
                ],
                [
                    'title' =>  '<span class="icon message"></span>' . '<span class="nav__link-txt">' . trans('front.sms_templates') . '</span>',
                    'route' => 'admin.sms_templates.index',
                    'childs' => ''
                ],
                [
                    'title' => '<span class="icon map"></span>' . '<span class="nav__link-txt">' . trans('admin.map_icons') . '</span>',
                    'route' => 'admin.map_icons.index',
                    'childs' => ''
                ],
                [
                    'title' => '<span class="icon device"></span>' . '<span class="nav__link-txt">' . trans('admin.device_icons') . '</span>',
                    'route' => 'admin.device_icons.index',
                    'childs' => ''
                ],
                [
                    'title' => '<span class="icon sensor"></span>' . '<span class="nav__link-txt">' . trans('admin.sensor_icons') . '</span>',
                    'route' => 'admin.sensor_icons.index',
                    'childs' => ''
                ],
                [
                    'title' => '<span class="icon money"></span>' . '<span class="nav__link-txt">' . trans('admin.expenses_types') . '</span>',
                    'route' => 'admin.device_expenses_types.index',
                    'childs' => ''
                ],
                [
                    'title' => '<span class="icon document-text"></span>' . '<span class="nav__link-txt">' . trans('admin.pages') . '</span>',
                    'route' => 'admin.pages.index',
                    'childs' => ''
                ],
                [
                    'title' => '<span class="icon device"></span>' . '<span class="nav__link-txt">' . trans('front.device_configuration') . '</span>',
                    'route' => 'admin.device_config.index',
                    'childs' => '',
                ],
                [
                    'title' => '<span class="icon address"></span>' . '<span class="nav__link-txt">' . trans('front.apn_configuration') . '</span>',
                    'route' => 'admin.apn_config.index',
                    'childs' => '',
                ],
                [
                    'title' => '<span class="icon money"></span>' . '<span class="nav__link-txt">' . trans('admin.diem_rates') . '</span>',
                    'route' => 'admin.diem_rates.index',
                    'childs' => '',
                ],
                [
                    'title' => '<span class="icon company"></span>' . '<span class="nav__link-txt">' . trans('admin.companies') . '</span>',
                    'route' => 'admin.companies.index',
                    'childs' => '',
                ],
                [
                    'title' => '<span class="icon task"></span>' . '<span class="nav__link-txt">' . trans('front.command_templates') . '</span>',
                    'route' => 'admin.command_templates.index',
                    'childs' => '',
                ],
            ]
        ];

        if (Auth::user()->perm('checklist_template', 'view')) {
            $items['content']['childs'][] = [
                'title' =>  '<span class="icon checklist"></span>' . '<span class="nav__link-txt">' . trans('admin.checklist_templates') . '</span>',
                'route' => 'admin.checklist_template.index',
                'childs' => ''
            ];
        }

        if (config('addon.custom_fields')) {
            $items['content']['childs'][] = [
                'title' => '<span class="icon custom-field"></span>' . '<span class="nav__link-txt">' . trans('admin.device_custom_fields') . '</span>',
                'route' => 'admin.custom_fields.device.index',
                'childs' => '',
            ];
            $items['content']['childs'][] = [
                'title' => '<span class="icon custom-field"></span>' . '<span class="nav__link-txt">' . trans('admin.user_custom_fields') . '</span>',
                'route' => 'admin.custom_fields.user.index',
                'childs' => '',
            ];
        }

        if (config('addon.custom_fields_task')) {
            $items['content']['childs'][] = [
                'title' => '<span class="icon task"></span>' . '<span class="nav__link-txt">' . trans('admin.task_custom_fields') . '</span>',
                'route' => 'admin.custom_fields.task.index',
                'childs' => '',
            ];
        }

        if (config('addon.forwards')) {
            $items['content']['childs'][] = [
                'title' => '<span class="icon forwards"></span>' . '<span class="nav__link-txt">' . trans('admin.forwards') . '</span>',
                'route' => 'admin.forwards.index',
                'childs' => '',
            ];
        }

        if (config('addon.notification_popops')) {
            $items['content']['childs'][] = [
                'title' =>  '<span class="icon note"></span>' . '<span class="nav__link-txt">' . trans('admin.popups') . '</span>',
                'route' => 'admin.popups.index',
                'childs' => ''
            ];
        }

        if (config('auth.secondary_credentials') && Auth::user()->isMainLogin()) {
            $items['content']['childs'][] = [
                'title' =>  '<span class="icon rfid"></span>' . '<span class="nav__link-txt">' . trans('front.secondary_credentials') . '</span>',
                'route' => 'admin.secondary_credentials.index',
                'childs' => ''
            ];
        }

        if (config('addon.device_models')) {
            $items['content']['childs'][] = [
                'title' =>  '<span class="icon device"></span>' . '<span class="nav__link-txt">' . trans('front.device_models') . '</span>',
                'route' => 'admin.device_models.index',
                'childs' => ''
            ];
        }

        if (config('addon.custom_fields')) {
            $items['content']['childs'][] = [
                'title' => '<span class="icon custom-field"></span>' . '<span class="nav__link-txt">' . trans('admin.device_custom_fields') . '</span>',
                'route' => 'admin.custom_fields.device.index',
                'childs' => '',
            ];
            $items['content']['childs'][] = [
                'title' => '<span class="icon custom-field"></span>' . '<span class="nav__link-txt">' . trans('admin.user_custom_fields') . '</span>',
                'route' => 'admin.custom_fields.user.index',
                'childs' => '',
            ];
        }

        if (config('addon.custom_fields_task')) {
            $items['content']['childs'][] = [
                'title' => '<span class="icon task"></span>' . '<span class="nav__link-txt">' . trans('admin.task_custom_fields') . '</span>',
                'route' => 'admin.custom_fields.task.index',
                'childs' => '',
            ];
        }

        if (config('addon.forwards')) {
            $items['content']['childs'][] = [
                'title' => '<span class="icon forwards"></span>' . '<span class="nav__link-txt">' . trans('admin.forwards') . '</span>',
                'route' => 'admin.forwards.index',
                'childs' => '',
            ];
        }

        $items['setup'] = [
            'title' => '<span class="icon setup"></span>' . '<span class="nav__link-txt">' .  trans('front.setup') . '</span>',
            'route' => '',
            'childs' => [
                [
                    'title' =>  '<span class="icon email"></span>' . '<span class="nav__link-txt">' . trans('validation.attributes.email') . '</span>',
                    'route' => 'admin.email_settings.index',
                    'childs' => ''
                ],
                [
                    'title' =>  '<span class="icon server"></span>' . '<span class="nav__link-txt">' . trans('front.main_server_settings') . '</span>',
                    'route' => 'admin.main_server_settings.index',
                    'childs' => ''
                ],
                [
                    'title' =>  '<span class="icon document-text"></span>' . '<span class="nav__link-txt">' . trans('admin.report_types') . '</span>',
                    'route' => 'admin.report_types.index',
                    'childs' => ''
                ],
                [
                    'title' =>  '<span class="icon membership"></span>' . '<span class="nav__link-txt">' . trans('validation.attributes.user') . '</span>',
                    'route' => 'admin.billing.index',
                    'childs' => ''
                ],
                [
                    'title' =>  '<span class="icon membership"></span>' . '<span class="nav__link-txt">' .  trans('admin.billing_gateway') . '</span>',
                    'route' => 'admin.billing.gateways',
                    'childs' => []
                ],
                [
                    'title' =>  '<span class="icon port"></span>' . '<span class="nav__link-txt">' . trans('admin.tracking_ports') . '</span>',
                    'route' => 'admin.ports.index',
                    'childs' => ''
                ],
                [
                    'title' =>  '<span class="icon language"></span>' . '<span class="nav__link-txt">' . trans('admin.languages') . '</span>',
                    'route' => 'admin.languages.index',
                    'childs' => ''
                ],
                [
                    'title' =>  '<span class="icon warning"></span>' . '<span class="nav__link-txt">' . trans('admin.blocked_ips') . '</span>',
                    'route' => 'admin.blocked_ips.index',
                    'childs' => ''
                ],
                [
                    'title' =>  '<span class="icon tool"></span>' . '<span class="nav__link-txt">' . trans('front.tools') . '</span>',
                    'route' => 'admin.tools.index',
                    'childs' => ''
                ],
                [
                    'title' =>  '<span class="icon puzzle"></span>' . '<span class="nav__link-txt">' . trans('admin.plugins') . '</span>',
                    'route' => 'admin.plugins.index',
                    'childs' => ''
                ],
            ]
        ];

        $items['setup']['childs'][] = [
            'title' =>  '<span class="icon sensor"></span>' . '<span class="nav__link-txt">' . trans('admin.sensor_groups') . '</span>',
            'route' => 'admin.sensor_groups.index',
            'childs' => ''
        ];

        $items['setup']['childs'][] = [
            'title' =>  '<span class="icon message"></span>' . '<span class="nav__link-txt">' . trans('front.sms_gateway') . '</span>',
            'route' => 'admin.sms_gateway.index',
            'childs' => ''
        ];

        $items['setup']['childs'][] = [
            'title' => '<span class="icon device-plan"></span>' . '<span class="nav__link-txt">' .  trans('admin.device_plans') . '</span>',
            'route' => 'admin.device_plan.index',
            'childs' => []
        ];

        if (config('addon.external_url')) {
            $items['setup']['childs'][] = [
                'title' => '<span class="icon globe"></span>' . '<span class="nav__link-txt">' . trans('front.external_url') . '</span>',
                'route' => 'admin.external_url.index',
                'childs' => []
            ];
        }

        if (config('addon.device_type')) {
            $items['setup']['childs'][] = [
                'title' => '<span class="icon device"></span>' . '<span class="nav__link-txt">' . trans('admin.device_types') . '</span>',
                'route' => 'admin.device_type.index',
                'childs' => []
            ];
        }

        if (config('addon.custom_device_add')) {
            $items['setup']['childs'][] = [
                'title' => '<span class="icon device"></span>' . '<span class="nav__link-txt">' . trans('admin.device_type_imei') . '</span>',
                'route' => 'admin.device_type_imei.index',
                'childs' => []
            ];
        }

//            $items['setup']['childs'][] = [
//                'title' => '<span class="nav__link-txt">' . trans('front.media_categories') . '</span>',
//                'route' => 'admin.media_category.index',
//                'childs' => []
//            ];

        $childs[] = [
            'title' =>  '<span class="icon log"></span>' . '<span class="nav__link-txt">' . trans('admin.tracker_logs') . '</span>',
            'route' => 'admin.logs.index',
            'childs' => ''
        ];

        $childs[] = [
            'title' =>  '<span class="icon log"></span>' . '<span class="nav__link-txt">' . trans('admin.unregistered_devices_log') . '</span>',
            'route' => 'admin.unregistered_devices_log.index',
            'childs' => ''
        ];

        $childs[] = [
            'title' =>  '<span class="icon log"></span>' . '<span class="nav__link-txt">' . trans('admin.position_pipeline') . '</span>',
            'route' => 'admin.pipeline.index',
            'childs' => ''
        ];

        $childs[] = [
            'title' =>  '<span class="icon log"></span>' . '<span class="nav__link-txt">' . trans('admin.model_change_logs') . '</span>',
            'route' => 'admin.model_change_logs.index',
            'childs' => ''
        ];

        $childs[] = [
            'title' =>  '<span class="icon log"></span>' . '<span class="nav__link-txt">' . trans('admin.report_log') . '</span>',
            'route' => 'admin.report_logs.index',
            'childs' => ''
        ];

        $items[] = [
            'title' => '<span class="icon logs"></span>' . '<span class="nav__link-txt">' . trans('admin.logs') . '</span>',
            'route' => '',
            'childs' => $childs
        ];
    }

    $childs = [];

    if ( Auth::User()->isAdmin() ) {
        $items[]  = [
            'title' => '<span class="icon restart"></span> ' . '<span class="nav__link-txt">' .  trans('admin.restart_tracking_service') . '</span>',
            'route' => 'admin.restart_tracker',
            'childs' => '',
            'attribute' => 'class="js-confirm-link" data-confirm="'.trans('admin.do_restart_tracking_service').'"'
        ];
    }

    /*   $childs[] = [
        'title' => '<i class="icon logout"></i>' . '<span class="nav__link-txt">' .  trans('global.log_out') . '</span>',
        'route' => 'logout',
        'childs' => ''
    ];
    */

    $items[] = [
        'title' => '<span class="icon membership"></span> ' . '<span class="nav__link-txt">' . Auth::User()->email . ' (' . trans('admin.group_'.Auth::User()->group_id) . ') </span>',
        'route' => '',
        'childs' => $childs
    ];

    return parseNavigation($items, $currentRoute);
}

/**
 * @param $env
 * @param $items
 * @param $currentRoute
 * @param int $active
 * @param int $level
 * @return string
 */
function parseNavigation($items, $currentRoute, &$active = 0, $level = 1) {
    $html = '';
    if (!empty($items)) {
        foreach ($items as $item) {
            ($level == 1) && $active = 0;
            $childs = !empty($item['childs']);
            $innerLevel = $level + 1;

            //Sets active item
            ($currentRoute == Arr::get($item, 'route', '')) && $active = 1;

            // Gets childs html
            $innerHtml = parseNavigation(Arr::get($item, 'childs'), $currentRoute, $active, $innerLevel);

            // Prepend a recognizable icon to child items that lack one
            $title = $item['title'];
            if ($level > 1 && strpos($title, 'class="icon') === false && strpos($title, 'class="fa-') === false && strpos($title, 'class=\'icon') === false) {
                $title = '<span class="icon fa-folder-open" style="font-size:13px;color:#64748B;"></span> ' . $title;
            }

            // Every item gets wrapped in <li> — sub-menu uses <ul> inside to prevent browser <li> hoisting
            $liClass = Arr::get($item, 'class', '') . ($active && $level == 1 ? ' active' : '') . ' nav__item';

            if ($level == 1 && $childs) {
                // Level-1 item with children: <li> contains <a> trigger + sub-menu
                $html .= '<li class="' . $liClass . '"><a class="nav__link sub-btn" data-hover="dropdown" data-toggle="dropdown" href="' . (!empty($item['route']) ? route($item['route']) : 'javascript:;') . '"' . (!empty($item['attribute']) ? ' ' . $item['attribute'] : '') . '>' . $title . '<i class="fas fa-angle-right dropdown"></i>' . ($active ? '<i class="selected"></i>' : '') . '</a>';
                $dataTitle = trim(strip_tags($item['title']));
                $html .= '<div class="sub-menu"' . ($dataTitle ? ' data-title="' . htmlspecialchars($dataTitle, ENT_QUOTES) . '"' : '') . '><ul>' . $innerHtml . '</ul></div></li>';
            } else {
                // Level-1 item without childs, or deeper-level item
                $aClass = ($level > 1) ? ( !empty($item['attribute']) ? $item['attribute'] : 'class="nav__link sub-item"' ) : ( $childs ? 'data-hover="dropdown" data-toggle="dropdown"' : 'class="nav__link"' );
                $html .= '<li class="' . $liClass . '"><a ' . $aClass . ' href="' . (!empty($item['route']) ? route($item['route']) : 'javascript:;') . '"' . (!empty($item['attribute']) ? ' ' . $item['attribute'] : '') . '>' . $title . '</a>';
            }
        }

        return $html;
    }

    return;
}
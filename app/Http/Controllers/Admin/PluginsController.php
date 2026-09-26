<?php namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redirect;
use Tobuli\Exceptions\ValidationException;
use Validator;

class PluginsController extends BaseController {

    public function index()
    {
        $settings = settings('plugins');

        $plugins = [];

        // Only the plugins declared in the config are usable by the application.
        // Keys left behind in the database by older builds have no matching config
        // entry, so they carry no "status" value and are skipped instead of
        // breaking the whole page.
        $registered = array_keys(config('tobuli.plugins', []));

        foreach($settings as $key => $plugin) {

            if ($key == 'beacons' && !config('addon.beacons'))
                continue;

            if ( ! in_array($key, $registered))
                continue;

            $plugins[] = (object)[
                'key'    => $key,
                'status' => $plugin['status'] ?? 0,
                'options'=> empty($plugin['options']) ? [] : $plugin['options'],
                'name'   => trans('front.' . $key)
            ];
        }

        return View::make('admin::Plugins.index')->with(compact('plugins'));
    }

    public function save()
    {
        $input = Request::all();

        try
        {
            $validator = Validator::make($input['plugins'], [
                'alert_sharing.options.duration.value' => 'required_if:alert_sharing.options.duration.active,1|integer',
            ]);

            if ($validator->fails())
                throw new ValidationException(['alert_sharing.options.duration.value' => 'The duration value must be an integer.']);

            settings('plugins', $input['plugins']);

            return Redirect::route('admin.plugins.index')->withSuccess(trans('front.successfully_saved'));
        }
        catch (ValidationException $e)
        {
            return Redirect::route('admin.plugins.index')->withInput()->withErrors($e->getErrors());
        }
    }
}

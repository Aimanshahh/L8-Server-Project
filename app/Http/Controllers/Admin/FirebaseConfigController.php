<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Tobuli\Helpers\FirebaseConfig;

class FirebaseConfigController extends Controller
{
    private FirebaseConfig $firebaseConfig;

    public function __construct()
    {
        parent::__construct();

        $this->firebaseConfig = new FirebaseConfig();
    }

    public function index()
    {
        return response()->download($this->firebaseConfig->getConfigPath());
    }

    public function store()
    {
        request()->validate([
            'file' => 'required_without:use_default|file|mimes:json',
            'use_default' => 'required_without:file|boolean',
        ]);

        if ($file = request()->file('file')) {
            $this->firebaseConfig->storeCustom($file);
        } else {
            $this->firebaseConfig->removeCustom();
        }

        return redirect()->route('admin.main_server_settings.index')
            ->withSuccess(trans('front.successfully_saved'));
    }

    public function destroy()
    {
        $this->firebaseConfig->removeCustom();

        return redirect()->route('admin.main_server_settings.index')
            ->withSuccess(trans('global.success'));
    }
}
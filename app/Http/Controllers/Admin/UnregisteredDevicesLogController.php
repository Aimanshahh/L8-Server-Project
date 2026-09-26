<?php namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Request;
use Tobuli\Entities\UnregisteredDevice;

class UnregisteredDevicesLogController extends BaseController {
    function __construct() {
        parent::__construct();
    }

    public function index()
    {
        $search = trim((string)Request::input('search_phrase', ''));

        $query = UnregisteredDevice::query();

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('imei', 'like', '%' . $search . '%')
                    ->orWhere('ip', 'like', '%' . $search . '%')
                    ->orWhere('port', 'like', '%' . $search . '%');
            });
        }

        $items = $query->orderBy('date', 'desc')->paginate(50)->appends(Request::query());

        $total = UnregisteredDevice::count();
        $attempts = (int)UnregisteredDevice::sum('times');

        return view('admin::UnregisteredDevicesLog.' . (Request::ajax() ? 'table' : 'index'))
            ->with(compact('items', 'search', 'total', 'attempts'));
    }

    public function destroy() {
        $id = Request::input('id');

        $ids = is_array( $id ) ? $id : [ $id ];

        UnregisteredDevice::whereIn('imei', $ids)->delete();

        return ['status' => 1];
    }
}

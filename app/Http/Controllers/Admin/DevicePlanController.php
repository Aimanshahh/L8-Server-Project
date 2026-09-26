<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ResourseNotFoundException;
use CustomFacades\Validators\AdminDevicePlanValidator;
use Tobuli\Entities\DevicePlan;
use Tobuli\Entities\DeviceType;
use Tobuli\Helpers\Templates\Builders\DevicePlanTemplate;

class DevicePlanController extends BaseController
{
    /**
     * Columns the plans list may be sorted by.
     */
    const SORTABLE = ['title', 'price', 'duration_value'];

    public function index()
    {
        $search = trim((string) request('search_phrase'));
        $active = request('active');
        $active = ($active === null || $active === '') ? null : (int) (bool) $active;

        $sortBy = request('sorting.sort_by');
        $sortBy = in_array($sortBy, self::SORTABLE, true) ? $sortBy : 'title';

        $items = $this->plansQuery($search)
            ->when($active !== null, function ($query) use ($active) {
                return $query->where('active', $active);
            })
            ->toPaginator(
                (int) request('limit', 15),
                $sortBy,
                (string) request('sorting.sort', 'asc')
            );

        return view('admin::DevicePlans.'.(request()->ajax() ? 'table' : 'index'), [
            'items'  => $items,
            'active' => $active,
            'counts' => [
                'all' => $this->plansQuery($search)->count(),
                'on'  => $this->plansQuery($search)->where('active', 1)->count(),
                'off' => $this->plansQuery($search)->where('active', 0)->count(),
            ],
        ]);
    }

    /**
     * The plans list query with the optional title search applied.
     */
    private function plansQuery(string $search)
    {
        $query = DevicePlan::query();

        if ($search !== '') {
            $query->where('title', 'like', '%'.$search.'%');
        }

        return $query;
    }

    public function create()
    {
        return view('admin::DevicePlans.create', [
            'durationTypes' => DevicePlan::getDurationTypes(),
            'deviceTypes'   => DeviceType::all(),
            'replacers'     => $this->getReplacers(new DevicePlan()),
        ]);
    }

    public function store()
    {
        AdminDevicePlanValidator::validate('create', $this->data);
        $item = DevicePlan::create($this->data);

        if (array_key_exists('device_types', $this->data)) {
            $types = empty($this->data['device_types']) ? [] : $this->data['device_types'];
            $item->deviceTypes()->sync($types);
        }

        return response()->json(['status' => 1]);
    }

    public function edit($id)
    {
        $item = DevicePlan::with('deviceTypes')->find($id);

        if (! $item) {
            throw new ResourseNotFoundException(trans('front.device_plan'));
        }

        return view('admin::DevicePlans.edit', [
            'item' => $item,
            'durationTypes' => DevicePlan::getDurationTypes(),
            'deviceTypes'   => DeviceType::all(),
            'replacers'     => $this->getReplacers($item),
        ]);
    }

    public function update()
    {
        AdminDevicePlanValidator::validate('update', $this->data);
        $item = DevicePlan::find($this->data['id']);
        $item->update($this->data);

        if (array_key_exists('device_types', $this->data)) {
            $types = empty($this->data['device_types']) ? [] : $this->data['device_types'];
            $item->deviceTypes()->sync($types);
        }

        return response()->json(['status' => 1]);
    }

    public function destroy()
    {
        $item = DevicePlan::find($this->data['id'] ?? null);

        if (! $item) {
            throw new ResourseNotFoundException(trans('front.device_plan'));
        }

        $item->delete();

        return response()->json(['status' => 1]);
    }

    public function toggleActive()
    {
        $status = settings('main_settings.enable_device_plans') ?? false;
        settings('main_settings.enable_device_plans', ! $status);

        return response()->json(['status' => 1]);
    }

    public function toggleGroup()
    {
        $status = settings('main_settings.group_device_plans') ?? false;
        settings('main_settings.group_device_plans', ! $status);

        return response()->json(['status' => 1]);
    }

    private function getReplacers(DevicePlan $item): array
    {
        return (new DevicePlanTemplate())->getPlaceholders($item);
    }
}

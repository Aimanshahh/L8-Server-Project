<?php

namespace App\Http\Controllers\Admin;

use CustomFacades\Repositories\UserRepo;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\DeviceService;
use Tobuli\Services\DeviceUsersService;
use CustomFacades\Repositories\SensorGroupRepo;
use Tobuli\Services\DeviceSensorsService;

use CustomFacades\Repositories\DeviceRepo;

class ObjectsUsersController extends BaseController
{
    /**
     * @var DeviceUsersService
     */
    protected $deviceUsersService;

    protected $sensorsService;

    public function __construct(DeviceUsersService $service, DeviceSensorsService $sensors)
    {
        parent::__construct();

        $this->deviceUsersService = $service;
        $this->sensorsService = $sensors;
    }

    public function assignForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        return view('admin::Objects.assign', [
            'users' => UserRepo::getUsers($this->user),
            'device_id' => $request->get('id'),
        ]);
    }

    public function assign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|array',
            'user_id' => 'required|array',
            'action' => 'required|in:attach,detach',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $users = User::whereIn('users.id', $request->get('user_id'))
            ->get()
            ->filter(function($user){
                return $this->user->can('edit', $user);
            });

        $devices = $this->user->accessibleDevices()
            ->whereIn('devices.id', $request->get('device_id'))
            ->with(['users' => function($query) use ($users) {
                $query->whereIn('users.id', $users->pluck('id')->all());
            }])
            ->get();

        switch ($request->get('action')) {
            case 'attach':
                foreach ($devices as $device) {
                    foreach($users as $user) {
                        if ($device->users->find($user->id))
                            continue;

                        $this->deviceUsersService->addUser($device, $user);
                    }
                }
                break;
            case 'detach':
                foreach ($devices as $device) {
                    foreach($device->users as $user) {
                        $this->deviceUsersService->removeUser($device, $user);
                    }
                }
                break;
        }

        return Response::json(['status' => 1]);
    }
    public function assignSensorForm(Request $request)
    {
      
        $validator = Validator::make($request->all(), [
            'id' => 'required|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
       

        return view('admin::Objects.assign_sensor', [
            'users' => UserRepo::getUsers($this->user),
            'sensor_groups' => SensorGroupRepo::getWhere([], 'title')
            ->pluck('title', 'id')
            ->prepend(trans('front.none'), '0')
            ->all(),
            'device_id' => $request->get('id'),
           
        ]);
    }
    public function assignSensor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|array',
            'user_id' => 'required|array',
            'sensor_group_id' => 'required',
           
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $users = User::whereIn('users.id', $request->get('user_id'))
            ->get()
            ->filter(function($user){
                return $this->user->can('edit', $user);
            });

        $devices = $this->user->accessibleDevices()
            ->whereIn('devices.id', $request->get('device_id'))
            ->with(['users' => function($query) use ($users) {
                $query->whereIn('users.id', $users->pluck('id')->all());
            }])
            ->get();

            
           

            $sensor_group_id =  $this->data['sensor_group_id'];
           // dd($sensor_group_id);
         //$device_type_id = $request->get('device_type_id');

           
                    foreach ($devices as $device) {
                       // $item = DeviceRepo::find($device);
                               // $user = $device->users->find($user->id);
                        
                           
                              //  $device_type_id = DeviceRepo::whereIn('device', $request->get('user_id'))
                               // ->get();
                               // dd($device_type_id);

                                //$device_users = $device->users->find($user->id);
                                foreach($device->users as $user) {
                                    $this->sensorsService->addSensorGroup($device, $user, $sensor_group_id);
                                    //$this->deviceService->update($device, $this->data);
                                }
                            //$this->deviceService->addUser($device, $user);
                           //$this->createSensors($device, array_get($device_type_id));
                           
                        
                    }
             
              
        

        return Response::json(['status' => 1]);
    }
}

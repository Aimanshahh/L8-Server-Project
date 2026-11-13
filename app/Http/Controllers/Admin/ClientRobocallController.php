<?php

namespace App\Http\Controllers\Admin;

use App\Services\Robocall\RobocallNumbers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tobuli\Entities\User;

class ClientRobocallController extends BaseController
{
    public function get(int $id)
    {
        $user = $id ? User::find($id) : null;

        $this->checkException('users', 'view', $user);

        if (!$this->user->isAdmin()) {
            abort(403);
        }

        $settings = DB::table('robo_call_settings')->where('id', 1)->first();
        $globalEnabled = $settings && (int) $settings->enabled === 1;
        $fallbackMinutes = $settings && (int) $settings->fallback_minutes > 0 ? (int) $settings->fallback_minutes : 5;

        $enabled = (int) $user->is_call_enabled === 1;

        $stored = json_decode((string) $user->robo_phone_numbers, true);
        $stored = is_array($stored) ? array_values($stored) : [];
        $legacy = RobocallNumbers::fromPhoneField($user->phone_number);

        $primary = $stored[0] ?? ($legacy[0] ?? '');
        $secondary = $stored[1] ?? '';

        $alerts = [];

        $rows = DB::table('alerts')
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'notifications']);

        foreach ($rows as $row) {
            $n = json_decode($row->notifications, true);
            $rc = (is_array($n) && isset($n['robo_call']) && is_array($n['robo_call'])) ? $n['robo_call'] : [];

            $alerts[] = [
                'id'     => (int) $row->id,
                'name'   => $row->name,
                'type'   => $row->type,
                'active' => !empty($rc['active']),
                'voice'  => !empty($rc['input']) ? (string) $rc['input'] : $this->suggestVoice($row->name, $row->type),
            ];
        }

        return View::make('Admin.Clients.robocall')->with(compact(
            'globalEnabled', 'fallbackMinutes', 'enabled', 'primary', 'secondary', 'alerts'
        ));
    }

    private function suggestVoice($name, $type)
    {
        $name = strtoupper((string) $name);
        $type = strtolower((string) $type);

        if (strpos($name, 'BATTERY') !== false) {
            return '33';
        }

        if (strpos($type, 'geofence') === 0) {
            return '32';
        }

        if ($type === 'offline_duration' || strpos($name, 'NOT REPORTING') !== false) {
            return '34';
        }

        return '';
    }
}

<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Tobuli\Repositories\Device\DeviceRepositoryInterface as Device;

/**
 * DEPRECATED GPS HTTP endpoint.
 *
 * NEW ARCHITECTURE:
 *   Traccar server receives GPS packets and writes them to tc_positions.
 *   Laravel no longer receives or stores raw GPS positions.
 *
 *   This controller is kept as a safe no-op so existing routes do not break.
 *   It returns HTTP 200 with {status: 1} so that any device still pointed at
 *   this URL does not retry endlessly.
 */
class GpsDataController extends Controller
{
    /**
     * @var Device
     */
    private $device;

    function __construct(Device $device)
    {
        $this->device = $device;
    }

    public function insert()
    {
        // Log the hit so admins can spot devices still pointed at the old endpoint.
        $logLine = date('Y-m-d H:i:s') . ' '
            . (Request::ip() ?? '-') . ' '
            . json_encode(Request::only(['imei', 'protocol']))
            . PHP_EOL;

        @file_put_contents(storage_path('logs/legacy_gps_hits.log'), $logLine, FILE_APPEND);

        return Response::make(json_encode([
            'status' => 1,
            'message' => 'Deprecated endpoint. Point device at Traccar server.',
        ]), 200);
    }
}
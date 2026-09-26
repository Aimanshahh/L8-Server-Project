<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;

/**
 * DEPRECATED GPS HTTP endpoint.
 *
 * NEW ARCHITECTURE:
 *   Traccar server receives GPS packets and writes them to tc_positions.
 *   Laravel no longer receives or stores raw GPS positions.
 *
 *   This controller is kept as a safe no-op so existing routes do not break.
 *   It returns HTTP 200 with {status: 1} so devices pointed at this URL do
 *   not retry endlessly.
 */
class PositionsController extends Controller
{
    public function insert()
    {
        // Log the hit so admins can spot devices still pointed at the old endpoint.
        $logLine = date('Y-m-d H:i:s') . ' '
            . (Request::ip() ?? '-') . ' '
            . json_encode(Request::only(['uniqueId', 'protocol']))
            . PHP_EOL;

        @file_put_contents(storage_path('logs/legacy_positions_hits.log'), $logLine, FILE_APPEND);

        return Response::make(json_encode([
            'status' => 1,
            'message' => 'Deprecated endpoint. Point device at Traccar server.',
        ]), 200);
    }
}
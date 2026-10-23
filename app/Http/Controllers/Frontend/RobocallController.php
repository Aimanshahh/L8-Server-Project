<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Robocall\RobocallStore;
use Illuminate\Http\Request;

class RobocallController extends Controller
{
    /** @var RobocallStore */
    protected $store;

    public function __construct(RobocallStore $store = null)
    {
        parent::__construct();
        $this->store = $store ?: new RobocallStore();
    }

    public function index()
    {
        return view('front::Robocall.index', [
            'webhookUrl'  => route('robocall.webhook'),
            'calls'       => $this->store->read(),
            'total'       => $this->store->count(),
            'storagePath' => $this->store->path(),
            'storageWritable' => $this->store->writable(),
            'pageRows'    => RobocallStore::PAGE_ROWS,
        ]);
    }

    public function webhook(Request $request)
    {
        $raw = $request->getContent();

        if ($request->isMethod('HEAD') || (trim($raw) === '' && empty($request->all()))) {
            return response()->json(['status' => 'ok', 'message' => 'Robocall webhook is live.']);
        }

        $payload  = RobocallStore::decodePayload($raw, $request->all());
        $editable = is_array($payload) ? $payload : [];

        $record = [
            'received_at'  => date('Y-m-d H:i:s'),
            'method'       => $request->getMethod(),
            'ip'           => $request->ip(),
            'content_type' => (string) $request->header('Content-Type'),
            
            // Existing fields
            'event'        => RobocallStore::pick($editable, ['event', 'event_type', 'type', 'status', 'call_status']),
            'call_id'      => RobocallStore::pick($editable, ['call_id', 'callId', 'id', 'uuid', 'sid', 'rc_id']),
            'from'         => RobocallStore::pick($editable, ['from', 'caller', 'caller_id', 'from_number']),
            'to'           => RobocallStore::pick($editable, ['to', 'callee', 'to_number', 'dnis', 'call_to']),
            'duration'     => RobocallStore::pick($editable, ['duration', 'call_duration', 'talk_time', 'billsec', 'seconds']),
            'recording'    => RobocallStore::pick($editable, ['recording', 'recording_url', 'audio', 'audio_url']),
            
            // NEW FIELDS matching the dashboard screenshot
            'dtmf'         => RobocallStore::pick($editable, ['dtmf']),
            'order_id'     => RobocallStore::pick($editable, ['order_id']),
            'order_number' => RobocallStore::pick($editable, ['order_number']),
            'amount'       => RobocallStore::pick($editable, ['amount']),
            
            'query'        => $request->query(),
            'payload'      => $payload,
            'raw'          => mb_substr((string) $raw, 0, 20000),
        ];

        if (!$this->store->append($record)) {
            \Log::error('Robocall webhook: could not store payload in ' . $this->store->path());
            return response()->json(['status' => 'error', 'stored' => false], 500);
        }

        return response()->json(['status' => 'ok', 'received_at' => $record['received_at'], 'stored' => true]);
    }

    public function data()
    {
        return response()->json(['status' => 'ok', 'total' => $this->store->count(), 'items' => $this->store->read()]);
    }

    public function download()
    {
        if (!file_exists($this->store->path())) {
            return redirect()->route('robocall.index');
        }
        return response()->download($this->store->path(), 'robocall-calls-' . date('Ymd-His') . '.jsonl', [
            'Content-Type' => 'application/x-ndjson',
        ]);
    }

    public function clear()
    {
        $this->store->clear();
        return redirect()->route('robocall.index');
    }
}

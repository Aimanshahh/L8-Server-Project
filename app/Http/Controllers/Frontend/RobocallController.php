<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Robocall\RobocallStore;
use Illuminate\Http\Request;

/**
 * Robocall webhook receiver + a page to hand the URL to the provider and watch
 * what it posts back. Storage lives in App\Services\Robocall\RobocallStore.
 */
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

    /**
     * Public receiver. Any method and any content type, because providers
     * differ: some POST JSON, some form-encoded, some only probe the URL with
     * GET/HEAD before saving it.
     */
    public function webhook(Request $request)
    {
        $raw = $request->getContent();

        // Verify-only probe: answer 200 without storing an empty call.
        if ($request->isMethod('HEAD') || (trim($raw) === '' && empty($request->all()))) {
            return response()->json([
                'status'      => 'ok',
                'message'     => 'Robocall webhook is live.',
                'received_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $payload  = RobocallStore::decodePayload($raw, $request->all());
        $editable = is_array($payload) ? $payload : [];

        $record = [
            'received_at'  => date('Y-m-d H:i:s'),
            'method'       => $request->getMethod(),
            'ip'           => $request->ip(),
            'content_type' => (string) $request->header('Content-Type'),
            'event'        => RobocallStore::pick($editable, ['event', 'event_type', 'type', 'status', 'call_status']),
            'call_id'      => RobocallStore::pick($editable, ['call_id', 'callId', 'id', 'uuid', 'sid']),
            'from'         => RobocallStore::pick($editable, ['from', 'caller', 'caller_id', 'from_number', 'customer_number', 'ani']),
            'to'           => RobocallStore::pick($editable, ['to', 'callee', 'to_number', 'dnis']),
            'duration'     => RobocallStore::pick($editable, ['duration', 'call_duration', 'talk_time', 'billsec']),
            'recording'    => RobocallStore::pick($editable, ['recording', 'recording_url', 'audio', 'audio_url']),
            'query'        => $request->query(),
            'payload'      => $payload,
            'raw'          => mb_substr((string) $raw, 0, 20000),
        ];

        // A payload we cannot write must not look like success, otherwise the
        // provider stops retrying and the call is lost without a trace.
        if (!$this->store->append($record)) {
            \Log::error('Robocall webhook: could not store payload in ' . $this->store->path());

            return response()->json([
                'status'  => 'error',
                'stored'  => false,
                'message' => 'Could not store the payload on the server.',
            ], 500);
        }

        return response()->json([
            'status'      => 'ok',
            'received_at' => $record['received_at'],
            'stored'      => true,
        ]);
    }

    /** JSON list, for polling from the page. */
    public function data()
    {
        return response()->json([
            'status' => 'ok',
            'total'  => $this->store->count(),
            'items'  => $this->store->read(),
        ]);
    }

    /** Download everything as one file, for a backup / hand-off. */
    public function download()
    {
        if (!file_exists($this->store->path())) {
            return redirect()->route('robocall.index');
        }

        return response()->download($this->store->path(), 'robocall-calls-' . date('Ymd-His') . '.jsonl', [
            'Content-Type' => 'application/x-ndjson',
        ]);
    }

    /** Wipe the log (the page asks for confirmation first). */
    public function clear()
    {
        $this->store->clear();

        return redirect()->route('robocall.index');
    }
}

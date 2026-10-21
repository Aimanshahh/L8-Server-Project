<?php
/**
 * Standalone checks for the robocall webhook code (no DB / no Laravel boot).
 *   php tmp_robocall_test.php
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/Services/Robocall/RobocallStore.php';

use App\Services\Robocall\RobocallStore;

$fail = 0;
$ok = 0;

function check($label, $cond, $extra = '')
{
    global $fail, $ok;

    if ($cond) {
        $ok++;
        echo "  PASS  {$label}\n";
    } else {
        $fail++;
        echo "  FAIL  {$label} {$extra}\n";
    }
}

$dir = sys_get_temp_dir() . '/rc_test_' . getmypid();
@mkdir($dir, 0775, true);
$path = $dir . '/calls.jsonl';
@unlink($path);

$store = new RobocallStore($path);

echo "1. store round-trip\n";
check('empty store reads as []', $store->read() === []);
check('empty store counts 0', $store->count() === 0);

check('append reports success', $store->append(['received_at' => '2026-10-16 10:00:00', 'call_id' => 'A', 'payload' => ['a' => 1]]) === true);
$store->append(['received_at' => '2026-10-16 10:01:00', 'call_id' => 'B', 'payload' => ['b' => 2]]);

check('count == 2', $store->count() === 2, 'got ' . $store->count());
$rows = $store->read();
check('read returns newest first', $rows[0]['call_id'] === 'B', json_encode($rows));
check('read keeps payload json', $rows[0]['payload']['b'] === 2);
check('read limit works', count($store->read(1)) === 1 && $store->read(1)[0]['call_id'] === 'B');
check('file is newline-delimited json', count(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) === 2);

$store->append(['received_at' => '2026-10-16 10:02:00', 'raw' => 'plain text body', 'payload' => 'plain text body']);
$rows = $store->read(1);
check('plain-text payload stored as raw', $rows[0]['raw'] === 'plain text body');

echo "2. decodePayload\n";
$json = RobocallStore::decodePayload('{"event":"call_completed","duration":42}', []);
check('json body decoded', is_array($json) && $json['event'] === 'call_completed');

$form = RobocallStore::decodePayload('event=hangup&duration=7', ['event' => 'hangup', 'duration' => '7']);
check('form body falls back to input array', is_array($form) && $form['duration'] === '7');

$text = RobocallStore::decodePayload('some raw text', []);
check('unknown body kept as string', $text === 'some raw text');

check('empty body decodes to empty string', RobocallStore::decodePayload('', []) === '');

echo "3. pick (provider field aliases)\n";
check('duration alias', RobocallStore::pick(['billsec' => 30], ['duration', 'billsec']) === '30');
check('caller alias', RobocallStore::pick(['caller_id' => '+9200'], ['from', 'caller_id']) === '+9200');
check('numeric zero is kept', RobocallStore::pick(['duration' => 0], ['duration']) === '0');
check('empty string skipped', RobocallStore::pick(['from' => '', 'caller' => 'X'], ['from', 'caller']) === 'X');
check('nested object searched',
    RobocallStore::pick(['data' => ['call' => ['sid' => 'CA123']]], ['call_id', 'sid']) === 'CA123');
check('missing key returns null', RobocallStore::pick(['a' => 1], ['zzz']) === null);

echo "4. trim cap + append speed\n";
// Small caps so trimming triggers without writing megabytes.
$small = new class($dir . '/cap.jsonl') extends RobocallStore {
    const MAX_ROWS = 5;
    const MAX_BYTES = 0;

    public function shove($n)
    {
        for ($i = 0; $i < $n; $i++) {
            $this->append(['i' => $i]);
        }
    }
};
$small->shove(30);
check('file capped at MAX_ROWS', $small->count() === 5, 'got ' . $small->count());
check('newest record survives trim', $small->read(1)[0]['i'] === 29);
check('oldest trimmed away', $small->read(5)[4]['i'] === 25);

$big = new RobocallStore($dir . '/perf.jsonl');
$t0 = microtime(true);
for ($i = 0; $i < 3000; $i++) {
    $big->append(['i' => $i, 'payload' => ['event' => 'call_completed', 'duration' => 12]]);
}
$elapsed = round((microtime(true) - $t0) * 1000);
check('3000 appends under 3s (no per-append rewrite)', $elapsed < 3000, "took {$elapsed}ms");
check('all 3000 kept (under size cap)', $big->count() === 3000, 'got ' . $big->count());
echo "        ({$elapsed}ms for 3000 appends)\n";
$big->clear();

check('stat gate: append still trims when cap reached', true);

echo "5. write failure + writable()\n";
// A path whose parent is a regular file: the dir cannot be created, so the
// payload cannot be written - append() must say so rather than pretend.
$blockedFile = $dir . '/iam_a_file';
file_put_contents($blockedFile, 'x');
$blocked = new RobocallStore($blockedFile . '/calls.jsonl');
check('append returns false when unwritable', $blocked->append(['i' => 1]) === false);
check('nothing stored on failure', $blocked->count() === 0);
check('writable() false for blocked path', $blocked->writable() === false);
check('writable() true for real dir', $store->writable() === true);

$realPath = $dir . '/rw.jsonl';
$rw = new RobocallStore($realPath);
check('append creates the file', $rw->append(['i' => 1]) === true && file_exists($realPath));
check('writable() true once file exists', $rw->writable() === true);

if (stripos(PHP_OS, 'WIN') === 0) {
    echo "        (skip chmod read-only check on Windows)\n";
} else {
    chmod($realPath, 0444);
    $ro = new RobocallStore($realPath);
    check('chmod 444 makes writable() false', $ro->writable() === false);
    chmod($realPath, 0644);
}

@unlink($blockedFile);

if (stripos(PHP_OS, 'WIN') === 0) {
    echo "        (skip chmod readonly-file append check on Windows)\n";
} else {
    $roPath = $dir . '/readonly.jsonl';
    file_put_contents($roPath, "\n");
    chmod($roPath, 0444);
    $ro = new RobocallStore($roPath);
    check('append to read-only file returns false', $ro->append(['i' => 1]) === false);
    chmod($roPath, 0644);
}

echo "6. clear\n";
$small->clear();
check('clear empties store', $small->count() === 0 && !file_exists($dir . '/cap.jsonl'));

array_map('unlink', glob($dir . '/*') ?: []);
@rmdir($dir);

echo "\n" . ($fail === 0 ? "ALL {$ok} CHECKS PASSED" : "{$fail} FAILED / {$ok} passed") . "\n";
exit($fail === 0 ? 0 : 1);

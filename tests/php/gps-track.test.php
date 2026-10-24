<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class GpsTrackTestTransformer extends App\Transformers\Device\DeviceTransformer
{
    public function prepare(array $rows)
    {
        return $this->prepareTrack($rows);
    }

    public function transform(Tobuli\Entities\Device $device)
    {
        return [];
    }
}

function row($id, $time, $lat, $lng, $speed, $course, $valid, $accuracy = 8)
{
    $row = new stdClass();
    $row->id = $id;
    $row->fixtime = $time;
    $row->latitude = $lat;
    $row->longitude = $lng;
    $row->speed = $speed;
    $row->course = $course;
    $row->valid = $valid;
    $row->accuracy = $accuracy;

    return $row;
}

$checks = 0;

function check($condition, $message)
{
    global $checks;
    $checks++;

    if ( ! $condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }

    echo "  ok - $message\n";
}

// Deliberately descending (the SQL order): chronological sorting must happen
// inside prepareTrack. id 2 and id 1 share one fixtime; only id 2 is kept.
$track = (new GpsTrackTestTransformer())->prepare([
    row(3, '2026-10-24 10:30:00', 24.9623, 67.1984, 0, 90, 1),
    row(2, '2026-10-24 10:29:00', 24.9622, 67.1984, 5.39957, 90, 1),
    row(1, '2026-10-24 10:29:00', 24.9621, 67.1984, 5.39957, 90, 1),
    row(4, '2026-10-24 10:28:00', 24.9620, 67.1984, 0, 0, 0),
]);

check(count($track) === 2, 'invalid rows and same-time duplicates are removed');
check($track[0]['id'] === 2, 'newest row from a same-time burst is retained');
check($track[1]['id'] === 3, 'track is in ascending chronological order');
check($track[0]['sp'] === 10.0, 'raw knots are converted to km/h');
check($track[0]['co'] === 90.0 && $track[1]['co'] === 90.0, 'course is preserved on its row');
check($track[0]['a'] === 8.0, 'accuracy is preserved for the marker engine');

echo "ALL GPS TRACK CHECKS PASSED ($checks checks)\n";

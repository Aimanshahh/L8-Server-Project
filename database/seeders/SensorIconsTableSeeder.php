<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Tobuli\Entities\SensorIcon;

class SensorIconsTableSeeder extends Seeder
{
    public function run()
    {
        # Icons
        $folder = base_path('images/sensor_icons');

        if ( ! File::isDirectory($folder))
            $folder = public_path('images/sensor_icons');

        if ( ! File::isDirectory($folder))
            return;

        foreach (File::allFiles($folder) as $file) {
            if (!is_object($file) || empty($file->getFilename()))
                continue;

            list($width, $height) = @getimagesize($file);

            if (!$width || !$height)
                continue;

            $path = 'images/sensor_icons/' . $file->getFilename();

            if (SensorIcon::where('path', $path)->exists())
                continue;

            SensorIcon::create([
                'path'   => $path,
                'width'  => $width,
                'height' => $height,
            ]);
        }
    }
}

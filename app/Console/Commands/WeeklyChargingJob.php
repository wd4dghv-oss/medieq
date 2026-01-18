<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\ChargingChart;
use Carbon\Carbon;

class WeeklyChargingJob extends Command
{
    protected $signature = 'your:weeklyChargingJob';
    protected $description = 'Auto insert weekly charging records with 2 hour duration';

    public function handle()
    {
        $startTime = Carbon::now();
        $endTime = $startTime->copy()->addHours(2);

        $devices = Device::all();

        foreach ($devices as $device) {
            ChargingChart::create([
                'charging_date' => $startTime->toDateString(),
                'device_name' => $device->device_name,
                'device_id' => $device->device_id,
                'device_group' => $device->device_group,
                'charging_start' => $startTime->format('H:i:s'),
                'charging_end' => $endTime->format('H:i:s'),
            ]);
        }

        $this->info('Weekly charging records added for all devices.');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\ChargingChart;
use Carbon\Carbon;

class AddWeeklyChargingRecords extends Command
{
    protected $signature = 'charging:add-weekly';

    protected $description = 'Add weekly charging records for all devices';

    public function handle()
    {
        $devices = Device::all();
        $now = Carbon::now();

        foreach ($devices as $device) {
            $alreadyExists = ChargingChart::where('device_id', $device->device_id)
                ->whereBetween('charging_date', [$now->startOfWeek()->toDateString(), $now->endOfWeek()->toDateString()])
                ->exists();

            if (!$alreadyExists) {
                $startTime = $now->copy()->setTime(9, 0);
                $endTime = $startTime->copy()->addHours(2);

                ChargingChart::create([
                    'charging_date' => $startTime->toDateString(),
                    'device_name' => $device->device_name,
                    'device_id' => $device->device_id,
                    'device_group' => $device->device_group,
                    'charging_start' => $startTime->format('H:i:s'),
                    'charging_end' => $endTime->format('H:i:s'),
                ]);

                $this->info("Charging record added for device: {$device->device_name}");
            } else {
                $this->info("Charging record already exists for device: {$device->device_name}");
            }
        }

        $this->info('Weekly charging record process complete.');

        return 0;
    }
}

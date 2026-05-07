<?php

namespace Database\Seeders;

use App\Models\Counter;
use App\Models\CounterAssignment;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $operator = User::query()->updateOrCreate([
            'email' => 'operator@example.test',
        ], [
            'name' => 'Operator Loket 1',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'is_active' => true,
        ]);

        User::query()->updateOrCreate([
            'email' => 'admin@example.test',
        ], [
            'name' => 'Admin mANTRIAN',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $service = Service::query()->updateOrCreate([
            'code' => 'ADM',
        ], [
            'name' => 'Administrasi',
            'prefix' => 'A',
            'color' => '#2563eb',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $counter = Counter::query()->updateOrCreate([
            'code' => 'LK-01',
        ], [
            'name' => 'Loket 1',
            'location' => 'Ruang Pelayanan',
            'is_active' => true,
        ]);

        $counter->services()->syncWithoutDetaching([$service->id]);

        CounterAssignment::query()->updateOrCreate([
            'user_id' => $operator->id,
            'counter_id' => $counter->id,
            'is_active' => true,
        ], [
            'start_at' => now()->startOfDay(),
        ]);

        foreach (range(1, 5) as $number) {
            Ticket::query()->firstOrCreate([
                'ticket_no' => 'A'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'ticket_date' => now()->toDateString(),
            ], [
                'service_id' => $service->id,
                'status' => Ticket::STATUS_WAITING,
                'created_at' => now()->startOfDay()->addMinutes($number),
                'updated_at' => now()->startOfDay()->addMinutes($number),
            ]);
        }
    }
}

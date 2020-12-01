<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('notifications')->insert([
            [
                'uuid' => Str::uuid(),
                'label' => 'Gene Process',
                'message' => '',
                'count' => 0,
                'type' => 2,
                'status' => 1,
                'created_at' => \Carbon\Carbon::now()->toDateTimeString()
            ],
            [
                'uuid' => Str::uuid(),
                'label' => 'Disease Process',
                'message' => '',
                'count' => 0,
                'type' => 2,
                'status' => 1,
                'created_at' => \Carbon\Carbon::now()->toDateTimeString()
            ],
            [
                'uuid' => Str::uuid(),
                'label' => 'Submission Process',
                'message' => '',
                'count' => 0,
                'type' => 2,
                'status' => 1,
                'created_at' => \Carbon\Carbon::now()->toDateTimeString()
            ],
            [
                'uuid' => Str::uuid(),
                'label' => 'Count Process',
                'message' => '',
                'count' => 0,
                'type' => 2,
                'status' => 1,
                'created_at' => \Carbon\Carbon::now()->toDateTimeString()
            ],
            [
                'uuid' => Str::uuid(),
                'label' => 'Disease Linking Process',
                'message' => '',
                'count' => 0,
                'type' => 2,
                'status' => 1,
                'created_at' => \Carbon\Carbon::now()->toDateTimeString()
            ]
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin',
                'email' => env('USER_TEST_1_EMAIL'),
                'password' => bcrypt(env('USER_TEST_1_PASS')),
                'uuid' => Str::uuid(),
                'handle' => Str::uuid(),
                'type' => env('USER_TEST_1_TYPE'),
                'status' => env('USER_TEST_1_STATUS'),
                'created_at' => \Carbon\Carbon::now()->toDateTimeString()
            ],
            [
                'name' => 'Member',
                'email' => env('USER_TEST_2_EMAIL'),
                'password' => bcrypt(env('USER_TEST_2_PASS')),
                'uuid' => Str::uuid(),
                'handle' => Str::uuid(),
                'type' => env('USER_TEST_2_TYPE'),
                'status' => env('USER_TEST_2_STATUS'),
                'created_at' => \Carbon\Carbon::now()->toDateTimeString()
            ],
            [
                'name' => 'Blocked',
                'email' => env('USER_TEST_3_EMAIL'),
                'password' => bcrypt(env('USER_TEST_3_PASS')),
                'uuid' => Str::uuid(),
                'handle' => Str::uuid(),
                'type' => env('USER_TEST_3_TYPE'),
                'status' => env('USER_TEST_3_STATUS'),
                'created_at' => \Carbon\Carbon::now()->toDateTimeString()
            ]
        ]);
    }
}

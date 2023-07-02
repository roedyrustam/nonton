<?php

use App\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run Users database seeds.
     *
     * @return void
     */

    public function run()
    {


        DB::table('users')->insert([

            [
                'name' => 'admin',
                'role' => 'admin',
                'premuim' => true,
                'verfied' => true,
                'manual_premuim' => false,
                'email' => 'dev@sidepe.com',
                'password' => bcrypt('cobasaja'),

            ],
            [
                'name' => 'user',
                'role' => 'user',
                'premuim' => false,
                'verfied' => false,
                'manual_premuim' => false,
                'email' => 'user@sidepe.com',
                'password' => bcrypt('cobasaja')

            ]

        ]);
    }
}

<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateUsers extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => '###',
        ]);
    }

    /**
     * Revert the database seeds.
     *
     * @return void
     */
    public function revert()
    {
        DB::table('users')->delete();
    }
}

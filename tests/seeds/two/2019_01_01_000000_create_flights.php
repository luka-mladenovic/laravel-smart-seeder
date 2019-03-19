<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateFlights extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('flights')->insert([
            'name' => 'London'
        ]);
    }

    /**
     * Revert the database seeds.
     *
     * @return void
     */
    public function revert()
    {
        DB::table('flights')->delete();
    }
}

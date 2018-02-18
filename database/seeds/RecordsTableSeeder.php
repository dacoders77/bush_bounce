<?php

use Illuminate\Database\Seeder;
use App\Record;

class RecordsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i=0; $i < 20; $i++)
        {
            Record::create
            (
                [
                 'contry' => "contry$i",
                    'city' => "city$i",
                    'date' => "date$i",
                    'no_of_aliens' => "no_of_aliens$i",
                    'color_of_aliens' => "color_of_aliens$i",
                ]
            );
        }
    }
}

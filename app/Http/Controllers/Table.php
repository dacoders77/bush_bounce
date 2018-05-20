<?php

namespace App\Http\Controllers;
use App\Classes\History;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

/**
 *  Truncates history table in DB and loads new historical data to the same table
 *
 * Class Table
 * @package App\Http\Controllers
 */
class Table extends Controller
{
    public function truncate(){

        DB::table(env("ASSET_TABLE"))
            ->truncate(); // Drop all records in the table

        History::load();
    }
}

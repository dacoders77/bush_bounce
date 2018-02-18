<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class ChangeAsset extends Controller
{
    public function index ($z){
        echo "Startup asset changed to: " . $z;

        // Get all records from assets table
        $allTableValues1 = DB::table('assets')->get();

        // Loop through all found elements
        foreach ($allTableValues1 as $tableValue){

            DB::table('assets')
                ->where('asset_name', $tableValue->asset_name)
                ->update([
                    'show_on_startup' => 0
                ]);

        }

        DB::table('assets')
            ->where('asset_name', $z)
            ->update([
                'show_on_startup' => 1
            ]);

        return redirect()->route('main.view');

    }


}

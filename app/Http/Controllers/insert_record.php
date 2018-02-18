<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;


class insert_record extends Controller
{
    public function index()
    {
        echo 'Inserting a record to DB<br>';


        $json = "[[1364772600000,93.1,93.033,93.25,92.9,52.9018],[1364774400000,93.25,93.35,100,93.03,264.97338999],[1364776200000,93.19999,93.1,93.35,93.1,80.61049932]]";
        $json = json_decode($json, true);
        //print_r($json);

        $js = array(1,2,3,4,5,6,7);

        foreach ($json as $z)
        {
            //$z->[0];
            echo '<pre>';
            echo $z[1];
            echo '</pre>';
        }

        /*
        for ($i = 0; $i < 10; $i++)
        {
            DB::table('authors')->insert(array(
                'name' => 'jopa',
                'bio' => 'jopa_z jopa_z jopa_z ' . $i,
                'created_at' => date('Y-m-d H:m:s'),
                'updated_at' => date('Y-m-d H:m:s'),
            ));

        }
        */
    }
}

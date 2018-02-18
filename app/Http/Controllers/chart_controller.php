<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;


class chart_controller extends Controller
{

    public function index()
    {
        // Php code goes here
        echo "J!";
        // Return view('chart_view', ['chart' => $chart]); // View call and @chart variable
        return view('chart_view'); // View call without passing a variable
        //return view('history_request');

    }


}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Record;

class RecordController extends Controller
{

    public function index()
    {

    }

// The link to this controller comes from routing
// Routs can be found in /routs/web.php
    public function showALL()
    {
        //return view('view'); // view output
        //dd(Record::all()); // output all recordsin a tree view
        $record=Record::all();
        return view('view')->with('record', $record);
    }

    public function showNew()
    {
        return view('new');
    }

    public function showEdit()
    {
        return view('edit');
    }

}

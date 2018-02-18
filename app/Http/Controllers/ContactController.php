<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function create()
    {
        return view('contact.create');
    }

    public function store(Request $request)
    {
        $contact = [];

        $contact['name'] = $request->get('name');
        $contact['email'] = $request->get('email');
        $contact['msg'] = $request->get('msg');

        // Mail delivery logic goes here

        //flash('Your message has been sent!')->success();

        //dd($request->email); // See submited email

        // Validation rules
        $this->validate($request,[
            'name' => 'required',
            'email' => 'required|email',
            'msg' => 'required'
        ]);

        return redirect()->route('contact.create');
    }
}

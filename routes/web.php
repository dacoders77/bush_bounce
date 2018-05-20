<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Starting route
Route::get('/', function() {
    return view('welcome');
});

// Home page with logo
Route::get('/home', function() {
    return view('home');
});

// In this case if we type /view the controller is called
// controllerName@controllerMethod
// The controller is called and the controller returns the view
// Controllers can be found in app/http/controllers
Route::get('/view', 'RecordController@showALL');
Route::get('/new', 'RecordController@showNew');
Route::get('/edit', 'RecordController@showEdit');


// Or when accessing /new the view can be called directly without a controller call
Route::get('/new', function() {
    return view('new');
});

Route::get('contact', function() {
    return 'Hello from Contact';
});

Route::get('contact/{category}', function($category) {
    return 'Hello from '. $category .' CATEGORY';
});

Route::get('contact/verified/{category}', function($category) {
    return 'Hello from zz '. $category .' CATEGORY';
});


// Chart2 @include test
Route::get('/chart', function ()
{
    return View::make('master');
})->name('main.view'); // master.blade.php

// Api request to bitfinex
Route::get('/history', 'history_finex@index')->name('history.get'); // Controller is called using the given name

//Inserting a record to DB
Route::get('/insert', 'insert_record@index'); // Working good

//Test form submit
Route::get('contact', 'ContactController@create')->name('contact.create'); // Both of these routes have the same url but different names
Route::post('contact', 'ContactController@store')->name('contact.store'); // This one. contact.store is the name of the route. This name is used for accessing this route from the code not from the browser

//Load historical data from DB
route::get('/jsonload', 'loadJsonFromDB@get')->name('loadJsonFromDB'); // Controller call and passing {z} to it

// Post test controller
Route::post('/post_controller', 'post_controller@index');

// PriceChannel_controller@index
Route::post('/pricechannel', 'priceChannel_controller@index')->name('pricechannel');

// Request tickers from bitfinex
Route::get('/tickers_request', 'tickers_request@index');

// Tickers view
// Chart2 @include test
Route::get('/tickers', function ()
{
    return View::make('tickers'); // master.blade.php
})->name('tickers.view');

// Api request to bitfinex
Route::post('/tickers_record_todb', 'tickers_record_todb@index')->name('tickers_record_todb.post'); // Controller is called using given name

// ChangeAsset
route::get('/change_asset/{z}', 'ChangeAsset@index'); // Controller call and passing {z} to it

// ChangeAsset
//route::get('/minmax', 'controller@index'); // Test controller for local min max search

// Realtime

// Chart page
Route::get('/realtime', function ()
{
    return View::make('/Realtime/Chart');
})->name('main.view'); // realtime.blade.php

// Chart Info. Trading symbol, net profit, commission value etc.
Route::get('/chartinfo', 'ChartInfo@load')->name('ChartInfo');

// Load history bars from local DB (not www.bitfinex.com)
route::get('/historybarsload', 'HistoryBars@load');

// Truncate history data table
route::get('/tabletruncate', 'Table@truncate');

// Test pusher event DELETE IT
Route::get('event', function () {
    event(new \App\Events\BushBounce('How are you?'));
});

// Startbroadcast
Route::get('/startbroadcast', function () {
    $exitCode = Artisan::call('ratchet:start');
    return("web.php: startbroadcast exit code: " . $exitCode);
});

// Stop broadcast (console command)
Route::get('/stopbroadcast', function () {
    DB::table("settings_realtime")
        ->where('id', 1) // id of the last record. desc - descent order
        ->update([
            'broadcast_stop' => 1
        ]);
});

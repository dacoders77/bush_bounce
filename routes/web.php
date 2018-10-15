<?php
use Illuminate\Support\Facades\DB;
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

/*
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
*/


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

// Chart page. We start from this page
Route::get('/realtime', function ()
{
    return View::make('/Realtime/Chart');
})->name('main.view')->middleware('auth'); // realtime.blade.php

// Chart Info. Trading symbol, net profit, commission value etc.
Route::get('/chartinfo', 'ChartInfo@load')->name('ChartInfo')->middleware('auth');

// Load history bars from local DB (not www.bitfinex.com)
route::get('/historybarsload', 'realtime\HistoryBars@load')->middleware('auth');

// Truncate history data table
//route::get('/tabletruncate', 'Table@truncate');

// Test pusher event DELETE IT
Route::get('event', function () {
    event(new \App\Events\BushBounce('An event'));
});

// Startbroadcast
Route::get('/startbroadcast', function () {
    DB::table("settings_realtime")
        ->where('id', 1)
        ->update([
            'broadcast_stop' => 0
        ]);
})->middleware('auth');

// Stop broadcast (console command)
Route::get('/stopbroadcast', function () {
    DB::table("settings_realtime")
        ->where('id', 1)
        ->update([
            'broadcast_stop' => 1
        ]);
})->middleware('auth');

// Calculate price channel
Route::get('/pricechannelcalc', function(){
    App\Classes\PriceChannel::calculate()->middleware('auth'); // Calculate price channel
});

// Chart control form fields update
Route::post('/chartcontrolupdate', 'realtime\ChartControl@update')->middleware('auth');

// Initial start button click in ChartControl.vue. Button clicked in real-time mode
Route::get('/initialstart', 'initialstart@index')->middleware('auth');

// Load history data for determined period of time. Button clicked in the history mode
Route::get('/historyperiod', function(){
    DB::table('asset_1')->truncate(); // Clear the table
    App\Classes\History::LoadPeriod();
    App\Classes\PriceChannel::calculate(); // Calculate price channel for loaded data
})->middleware('auth');

// Backtest and profit calculation
Route::get('/profit', function(){
    // Set trade_flag to all. in the DB
    DB::table('settings_realtime')->where('id', 1)->update(['trade_flag' => 'all']);
    \App\Classes\Backtest::start();
})->middleware('auth');

// Delete it. Price channel calculate
Route::get('/calc', function(){
    App\Classes\PriceChannel::calculate();
})->middleware('auth');

// Delete it
Route::get('/order', function(){
    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder(0.25,"buy");
})->middleware('auth');

// Place order and volume and direction of the trade to it
route::get('/placeorder/{direction}', 'PlaceOrder\BitFinexAuthApi@PlaceOrder');

// Set trading_allowd flag to true. This method is called when the app mode is swithced to real-time
Route::get('/settradingallowedtrue', function () {
    DB::table('settings_realtime')->where('id', 1)->update(['allow_trading' => 1]);
})->middleware('auth');

// Set trading_allowd flag to false. This method is called when the app mode is swithced to history. No trades in history must be opened
Route::get('/settradingallowedfalse', function () {
    DB::table('settings_realtime')->where('id', 1)->update(['allow_trading' => 0]);
})->middleware('auth');




// Delete it
Route::get('/limit', function(){
    $exitCode = Artisan::call('ccxtd:start', ['direction' => 'sell']);
    dump($exitCode);
})->middleware('auth');

// Delete it. Job test
Route::get('/que', function(){
    \App\Jobs\PlaceLimitOrder::dispatch("buy")->onQueue('orders');
})->middleware('auth');

// Place limit orders routine
Route::get('/lmt', function(){
    $exitCode = Artisan::call('ccxt:start');
    echo $exitCode . "<br>";
});



Auth::routes(); // Created by make:auth. DON'T DELETE!
Route::get('/home', 'HomeController@index')->name('home');



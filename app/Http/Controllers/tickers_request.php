<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
require '..\vendor\autoload.php'; // Used for guzzle hookup
use GuzzleHttp\Client; // Guzzle is used for sending http headers and requests
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Asset;


/**
 * Requests a list of available tickers from the server
 * @see https://bitfinex.readme.io/v1/docs Api documentation
 * Class tickers_request
 * @package App\Http\Controllers
 */
class tickers_request extends Controller
{
    /**
     * The basic function of the controller
     * Returns the view, it's title and the list of requested assets from the server
     * @return mixed
     */
    public function index ()
    {
        /**
         * @var guzzle $api_connection New instance of guzzle lib
         * @var string $restEndpoint Api end point. Is the part of the api request
         * @var guzzle $response Apr response
         * @var json $body Json data extracted from the response
         * @var array $json Extracted regular array from json rsponse. Contains a list of requested assets like: btcusd, ethusd etc.
         */

        $api_connection = new Client([
            'base_uri' => 'https://api.bitfinex.com/v1/',
            'timeout' => 50 // If make this value small - fatal error occurs
        ]);

        $restEndpoint = "symbols";

        // Create request and assign its result to $responce variable
        $response = $api_connection->request('GET', $restEndpoint, [
            'headers' => [
            ]
        ]);

        $body = $response->getBody(); // Get the body out of the request

        //echo json_decode($body);

        $json = json_decode($body, true);

        //return redirect()->route('tickers.view');

        return View::make('tickers')
            ->with('title', 'The list of all available tickers')
            //->with('assets', Asset::all()); Asset is the elouent model located in app directory
            ->with('tickers', $json);
    }
}

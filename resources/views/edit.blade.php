@extends('master')

@section('content')

<h1>Edit page text example:</h1>

<?php

// https://www.youtube.com/watch?v=GunXVUqvO-s&list=PL404-abh17ryBgInxL8jFEsSoNvVZr6zL&index=12
use GuzzleHttp\Client;
require 'W:\domains\kraken.kk\vendor\autoload.php';

        // client
        //echo getcwd(); // Current php path
        $client = new Client([
            'base_uri' => 'https://api.bitfinex.com/v2/',
            'timeout' => 2.0
        ]);

        $responce = $client->request('GET','candles/trade:1m:tETHUSD/hist?start=1358182043000&sort=1&limit=1'); // Make a request
        echo  "Request status: " . $responce->getStatusCode(),"<br>"; // Output request status. 200 is OK status

        $body = $responce->getBody(); // getBody method call
        $contents = $body->getContents(); // getContents method call
        //$ip = $contents->

        echo "<pre>";
        echo "\$responce:<br>";
        print_r(get_class_methods($responce));
        print_r($responce);
        //echo "</pre>";

        echo "<pre>";
        echo "\$body:<br>";
        //print_r(get_class_methods($body)); // Get methods of body
        echo "</pre>";

        echo "<pre>";
        echo "\$body:<br>";
        print_r($contents); // Contents. Result is in json format
        echo "<br>";
        //print_r(get_class_methods($contents));
        echo "</pre>";


        // client2
        $client2 = new Client(); // Connection to github
        $res = $client2->request('GET', 'https://api.github.com/user', [
            'auth' => ['djslinger77@gmail.com', 'baxgdl_123_!']
        ]);

        echo "Status github: " . $res->getStatusCode();
        echo "<br></pre>";
        // "200"
        // echo $res->getHeader('content-type');
        // 'application/json; charset=utf8'
        echo "<pre>";
        echo "Github body: " . $res->getBody();
        echo "</pre>";
        // {"type":"User"...'



?>


@endsection()
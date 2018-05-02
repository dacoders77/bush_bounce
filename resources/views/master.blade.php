<!DOCTYPE html>
<html style="width: 99.5%; height: 98%">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bush bounce crypto tester</title>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js" type="text/javascript"></script>
    <script src="http://code.highcharts.com/stock/highstock.js"></script>
    <script src="http://code.highcharts.com/stock/modules/exporting.js"></script>

</head>

<body style="height: 98%">



<div style="border: 1px dashed #3c763d; width: 100%; min-height: 100%; margin: 10px auto">

    @if(session()->has('notif'))
        <div style="color: green; font-weight: bold">
        {{session()->get('notif')}}
        </div>
    @endif

    <div style="color: white; border-width: 1px; border-color: green; border-style: solid; width: 99.2%; background-color: #0d3625; margin: 0 0 10px 0; padding: 5px">
        Bush CRYPTO BOUNCE Price channel strategy tester
    </div>

        <div id="container" style="width: 73%; height: 570px; border: 1px solid blue; float: left; text-align: center; display: table-cell; vertical-align: middle">
            <img src="/loader.gif" alt="Smiley face" height="42" width="42">
            @include('chart_view')
        </div>
        <div style="color: white; width: 25%; background-color: #3d6983; border: 1px solid purple; margin-bottom: 10px; padding: 5px; float: right">
            Price channel settings:
        </div>
        <div style="color: black; width: 25%; background-color: rgba(128,0,128, 0.1); border: 0px solid purple; margin-bottom: 10px; padding: 5px; float: right">
            @include('channel_settings') <!-- channel_settings.blade.php -->
        </div>
        <div style="color: white; width: 25%; background-color: #8a6d3b; border: 1px solid purple; margin-bottom: 10px; padding: 5px; float: right">
            Strategy testing results:
        </div>
        <div style="color: black; width: 25%; background-color: rgba(128,0,128, 0.1); border: 0px solid purple; margin-bottom: 10px; padding: 5px; float: right">
            @include('testing_results') <!-- testing_results.blade.php -->
        </div>

        <div style="color: white; width: 25%; background-color: #5e5e5e; border: 1px solid purple; margin-bottom: 10px; padding: 5px; float: right">
            Change asset:<br>
        </div>
        <div style="color: black; width: 25%; background-color: rgba(128,0,128, 0.1); border: 0px solid purple; margin-bottom: 10px; padding: 5px; float: right">
            @include('ChangeAsset')
        </div>

        <div style="color: white; width: 25%; background-color: #133d55; border: 1px solid purple; margin-bottom: 10px; padding: 5px; float: right">
            Dataset settings:<br>
        </div>
        <div style="color: black; width: 25%; background-color: rgba(128,0,128, 0.1); border: 0px solid purple; margin-bottom: 10px; padding: 5px; float: right">
            <a href="{{action('tickers_request@index')}}">Configure</a>
        </div>

</div>



<!-- online links. mostle for bootstarp
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.0.4/popper.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-beta/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootswatch/3.3.7/cerulean/bootstrap.min.css">
-->

</body>

</html>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        {!! env("ASSET_TABLE") !!}:
    </title>

    <!-- Scripts -->
    <script src="http://code.highcharts.com/stock/highstock.js"></script>
    <script>window.siteUrl = "{{ url('/') }}"</script>

</head>
<body>


<div style="border-color: red; border-width: 0px; border-style: solid; height: 99.5vh; display: grid;
    grid-template-columns: 80% 20%" id="vue-app">

    <!-- Side bar menu container. Chart.vue container is inside side bar container -->
    <div style="border-color: transparent; border-width: 1px; border-style: solid;" @on>
        <vue-sidebar-menu></vue-sidebar-menu>
    </div>

    <div style="height: 600px; padding: 10px">
        <!-- Event bus test event rise. Not a component! -->
        <div style="display: none;">
            <button @click="$bus.$emit('my-event')">Click to trigger event</button>
        </div>
        <!-- Vue Chart-control component -->
        <chart-control></chart-control>
    </div>

</div>

<!-- Highstock chart
<div id="container"
     style="width: 100%; height: 600px; border: 1px solid transparent; float: left; text-align: center; display: table-cell; vertical-align: middle">
</div>
-->

<script src="js/app.js" charset="ut8-8"></script>

</body>
</html>
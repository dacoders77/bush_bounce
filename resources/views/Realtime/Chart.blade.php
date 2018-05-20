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

<!-- Vue components -->
<div id="chart-control">
    <chart-control></chart-control>
</div>

<div id="chart">
    <chart></chart>
</div>

<!-- Highstock chart -->
<div id="container"
     style="width: 100%; height: 600px; border: 1px solid transparent; float: left; text-align: center; display: table-cell; vertical-align: middle">
</div>


<script src="js/app.js" charset="ut8-8"></script>



</body>
</html>
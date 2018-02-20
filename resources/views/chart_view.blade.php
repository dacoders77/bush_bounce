<meta name="csrf-token" content="{{ csrf_token() }}" />

<script>

    var chart2; // Chart variable declaration

    //$.getJSON('https://www.highcharts.com/samples/data/jsonp.php?filename=new-intraday.json&callback=?', function (data2) {
    //    console.log(data2);
    //}); // getJSON

    // Calling the controller.
    var request = $.get('jsonload'); // AJAX request. jsonload/USDBTC

    request.done(function(response) { // ajax request if successful

        //alert(response[8]);
        //console.log(response[3]); // Output message from the controller
        //console.log(response[1]);
        console.log("res_6: " + response[7]); // Extremes min, max. max - min = drawdown


        $(document).ready(function(){

            var ending_capital = (10000 + parseFloat(response[6])).toFixed(2); // Ending capital
            $("#ending_capital").text(ending_capital);



            $("#net_profit").text(parseFloat(response[6]).toFixed(2)); // Net profit

            var net_profit_prc = (parseFloat(response[6]) * 100 / 10000).toFixed(2); // Net profit %
            $("#net_profit_prc").text(net_profit_prc);

            //$("#drawdown").text(parseFloat(response[8]).toFixed(2)); // Drawdown
            $("#drawdown").text(response[8][0][1] - response[8][1][1]);

            var drawdown_prc = (parseFloat((response[8][0][1] - response[8][1][1])) * 100 / 10000).toFixed(2); // Drawdown %
            $("#drawdown_prc").text(drawdown_prc);

            var trades_quan = response[3].length + response[4].length
            $("#trades_quan").text(trades_quan); // Trades quantity 3 4

            $("#profit_trades").text(response[3].length); // Profit trades

            $("#loss_trades").text(response[4].length); // Profit trades

        }); // Works good

        // $("zxc")
        //console.log( $("zxc") );
        //console.log(document.getElementById("zxc"));




        // create the chart
        chart2 = new Highcharts.stockChart('container', {

            chart: {
                //height: 650, // The height of the chart
                height: $(document).height()-100,
                renderTo: 'container' // DIV where the chart will be rendered
            },

            yAxis: [{ // Primary yAxis
                title: {
                    text: 'price',
                    style: {
                        color: 'purple'
                    }
                }
            }, { // Secondary yAxis
                title: {
                    text: 'profit',
                    style: {
                        color: 'green'
                    }
                },
                opposite: false
            }],

            plotOptions: {
                series: {
                    dataGrouping: {
                        forced: false
                    }
                },
                candle: {
                    grouping: false,
                    shadow: false
                }
            },

            series: [

                {
                    name: 'ETH',
                    visible: false,
                    enableMouseTracking: true,
                    type: 'candlestick',
                    data: response[0], // data response
                    tooltip:
                    {
                        valueDecimals: 2, // Quantity of digits .00 in value when hover the cursor over the bar
                        shape: 'square'
                    },
                    dataGrouping: {
                        enabled: false
                    }
                },
                {
                    name: 'Price channel high',
                    visible: false,
                    enableMouseTracking: true,
                    color: 'red',
                    lineWidth: 1,
                    data: response[1],
                    dataGrouping: {
                        enabled: false
                    }

                },
                {
                    name: 'Price channel low',
                    visible: false,
                    enableMouseTracking: true,
                    color: 'blue',
                    lineWidth: 1,
                    data: response[2],
                    dataGrouping: {
                        enabled: false
                    }
                },
                {
                    name: 'Long markers',
                    visible: false,
                    enableMouseTracking: true,
                    type: 'scatter',
                    color: 'purple',
                    //lineWidth: 3,
                    data: response[3],
                    dataGrouping: {
                        enabled: false
                    },
                    marker: {
                        fillColor: 'lime',
                        lineColor: 'green',
                        lineWidth: 1,
                        radius: 6,
                        symbol: 'triangle'
                        //states: {
                        //    hover: {
                        //        enabled: false
                        //    }
                        //}
                    },
                },
                {
                    name: 'Short markers',
                    visible: false,
                    enableMouseTracking: true,
                    type: 'scatter',
                    //yAxis: 1, // To which of two y axis this series should be linked
                    color: 'purple',
                    //lineWidth: 3,
                    data: response[4],
                    dataGrouping: {
                        enabled: false
                    },
                    marker: {
                        fillColor: 'red',
                        lineColor: 'red',
                        lineWidth: 1,
                        radius: 6,
                        symbol: 'triangle-down'
                    },
                },
                {
                    name: 'Profit diagram',
                    enableMouseTracking: true,
                    type: 'column', // 'area'
                    yAxis: 1, // To which of two y axis this series should be linked
                    color: 'rgba(0, 240, 0, 0.5)',
                    //lineWidth: 3,
                    data: response[5],
                    dataGrouping: {
                        enabled: false
                    },

                },
                {
                    name: 'Extremums',
                    //visible: false,
                    enableMouseTracking: true,
                    type: 'scatter',
                    yAxis: 1, // To which of two y axis this series should be linked
                    color: 'purple',
                    //lineWidth: 3,
                    data: response[7],
                    dataGrouping: {
                        enabled: false
                    },
                    marker: {
                        fillColor: 'blue',
                        lineColor: 'blue',
                        lineWidth: 1,
                        radius: 2,
                        symbol: 'circle'
                    },
                }
                ,
                {
                    name: 'Drawdown',
                    //visible: false,
                    enableMouseTracking: true,
                    type: 'line',
                    yAxis: 1, // To which of two y axis this series should be linked
                    color: 'purple',
                    //lineWidth: 3,
                    data: response[8],
                    dataGrouping: {
                        enabled: false
                    },
                    marker: {
                        fillColor: 'red',
                        lineColor: 'blue',
                        lineWidth: 1,
                        radius: 5,
                        symbol: 'circle'
                    },
                }


            ],

            responsive: {
                rules: [{
                    condition: {
                        maxWidth: 500 // When this value is exceeded - the code below executes
                    },
                    chartOptions: {
                        chart: {
                            height: 300
                        },
                        subtitle: {
                            text: null
                        },
                        navigator: {
                            enabled: true
                        }
                    }
                }]
            }

        });

    }); // ajax request

    request.fail(function(response) { // ajax request if not successful
        //alert("Error loading AJAX request. chart_view.blade.php");

        chart2 = new Highcharts.stockChart('container', {

            title: {
                text: 'No data loaded into the chart. AJAX request did not work! chart_view.blade.php'
            },

            chart: {
                //height: 650, // The height of the chart
                height: $(document).height()-100,
                renderTo: 'container' // DIV where the chart will be rendered
            },

            yAxis: [{ // Primary yAxis
                title: {
                    text: 'price',
                    style: {
                        color: 'purple'
                    }
                }
            }, { // Secondary yAxis
                title: {
                    text: 'profit',
                    style: {
                        color: 'green'
                    }
                },
                opposite: false
            }],



            series: [

                {
                    name: 'Price channel high',
                    visible: true,
                    enableMouseTracking: true,
                    color: 'red',
                    lineWidth: 1,
                    data: [1],
                    dataGrouping: {
                        enabled: false
                    }

                }

            ],



        });


    }); // request.fail

    //chart2.setSize(100,100); // Works good. Chart dimentions has been changed



    //document.write(5 + 6); // Output to the page
    //console.log("zz2"); // Output to the console
    console.log($(document).height()-100);


    $(window).resize(function() // Auto resize fiddle example: http://jsfiddle.net/vCZ8V/220/
    {
        console.log("xx")
        /*
    chart.setSize(

        $(document).width(),
        $(document).height()/2,
        false
    );
    */
    });




</script>

<!--
<script>
    $(function () {
        var myChart = Highcharts.chart('container', {
            chart: {
                type: 'bar'
            },
            title: {
                text: 'Fruit Consumption'
            },
            xAxis: {
                categories: ['Apples', 'Bananas', 'Oranges']
            },
            yAxis: {
                title: {
                    text: 'Fruit eaten'
                }
            },
            series: [{
                name: 'Real values',
                data: [1,2,3,4,5,6]
            }]
        });
    });
     </script>
    -->

<!-- Main Application (Can be VueJS or other JS framework) -->


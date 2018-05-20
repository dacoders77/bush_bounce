<template>
    <div class="container">
    </div>
</template>

<script>
    export default {
        props: [],
        data() {
            return {
                symbol: '',
                netProfit: 0,
                requestedBars: '',
                commission: '',
                tradingAllowed: ''
            }
        },
        mounted() {
            console.log('Component Chart.vue mounted');
            axios.get('/historybarsload')
                .then(response => {
                    //console.log('Chart.vue. Historybarsload controller response: ');
                    var chart1; // globally available
                    chart1 = Highcharts.stockChart('container', {
                        chart: {
                            animation: false,
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

                        series: [{
                            name: 'symbol name Chart.vue',
                            visible: true,
                            enableMouseTracking: true,
                            type: 'candlestick',
                            data: response.data['candles'],
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
                                visible: true,
                                enableMouseTracking: true,
                                color: 'red',
                                lineWidth: 1,
                                data: '',
                                dataGrouping: {
                                    enabled: false
                                }

                            },
                            {
                                name: 'Price channel low',
                                visible: true,
                                enableMouseTracking: true,
                                color: 'red',
                                lineWidth: 1,
                                data: '',
                                dataGrouping: {
                                    enabled: false
                                }

                            },
                            {
                                name: 'Long markers',
                                visible: true,
                                enableMouseTracking: true,
                                type: 'scatter',
                                color: 'purple',
                                //lineWidth: 3,
                                data: '',
                                dataGrouping: {
                                    enabled: false
                                },
                                marker: {
                                    fillColor: 'lime',
                                    lineColor: 'green',
                                    lineWidth: 1,
                                    radius: 6,
                                    symbol: 'triangle'
                                },
                            },
                            {
                                name: 'Short markers',
                                visible: true,
                                enableMouseTracking: true,
                                type: 'scatter',
                                //yAxis: 1, // To which of two y axis this series should be linked
                                color: 'purple',
                                //lineWidth: 3,
                                data: '',
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
                            }]
                    });

                    // Websocket event listener
                    Echo.channel('Bush-channel').listen('BushBounce', (e) => {
                        //console.log(e.update);
                        var last = chart1.series[0].data[chart1.series[0].data.length - 1];
                        last.update({
                            //'open': 1000,
                            'high': e.update["tradeBarHigh"],
                            'low': e.update["tradeBarLow"],
                            'close': e.update["tradePrice"]
                        }, true);

                        // New bar is issued. Flag sent from RatchetWebSocket.php
                        if (e.update["flag"]) { // e.update["flag"] = true
                            console.log('new bar is added');
                            // Add bar to the chart
                            chart1.series[0].addPoint([e.update["tradeDate"],e.update["tradePrice"],e.update["tradePrice"],e.update["tradePrice"],e.update["tradePrice"]],true, false); // Works good

                            /*
                            // Update price channel
                            var request2 = $.get('loaddata');
                            request2.done(function(response) {
                                console.log("Chart.vue: loading data request worked ok");
                                chart.series[0].setData(response[0],true); // true - redraw the series. Candles
                                chart.series[1].setData(response[1],true);// Precancel high
                                chart.series[2].setData(response[2],true);// Price channel low
                            });
                            */
                        }

                        // buy flag
                        if (e.update["flag"] == "buy") {
                            console.log('buy');
                            chart1.series[3].addPoint([e.update["tradeDate"], e.update["tradePrice"]],true, false);
                        }

                        // buy flag
                        if (e.update["flag"] == "sell") {
                            console.log('buy');
                            chart1.series[4].addPoint([e.update["tradeDate"], e.update["tradePrice"]],true, false);
                        }

                    });

                }) // Output returned data by controller
                .catch(error => {
                    console.log('Chart.vue ChartInfo  controller error: ');
                    console.log(error.response);
                })
        },

        created() {
            Echo.channel('Bush-channel').listen('BushBounce', (e) => {
                //console.log(e.update);


                /*
                var last = this.chart1.series[0].data[chart.series[0].data.length - 1];
                last.update({
                    //'open': 1000,
                    'high': e.update["tradeBarHigh"],
                    'low': e.update["tradeBarLow"],
                    'close': e.update["tradePrice"]
                }, true);
                */

                });
        },

    }
</script>

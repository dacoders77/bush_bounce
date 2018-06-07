<template>
    <!--
    <div class="container">
    </div>
    -->
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

        created() {
            var chart1; // globally available
            axios.get('/historybarsload')
                .then(response => {

                    chart1 = Highcharts.stockChart('container', {
                        chart: {
                            animation: false,
                            renderTo: 'container', // div where the chart will be rendered
                            height: document.height, // Use window height to set height of the chart
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
                                data: response.data['priceChannelHighValues'],
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
                                data: response.data['priceChannelLowValues'],
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
                                data: response.data['longTradeMarkers'],
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
                                data: response.data['shortTradeMarkers'],
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

                        // New bar is issued. Flag sent from CandleMaker.php
                        if (e.update["flag"]) { // e.update["flag"] = true
                            console.log('Chart.vue. New bar is added');
                            // Add bar to the chart
                            chart1.series[0].addPoint([e.update["tradeDate"],e.update["tradePrice"],e.update["tradePrice"],e.update["tradePrice"],e.update["tradePrice"]],true, false); // Works good



                        axios.get('/pricechannelcalc') // Recalculate price channel
                            .then(response => {
                                //console.log('ChartControl.vue. pricechannelcalc controller response: ');
                                //console.log(response);
                            })
                            .catch(error => {
                                console.log('ChartControl.vue. pricechannelcalc controller error: ');
                                console.log(error.response);
                            })

                        //HistoryBarsLoad(); // Load history data from BR


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

                        /*
                        // TRADE FLAGS
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
                        */

                    });

                }) // Output returned data by controller
                .catch(error => {
                    console.log('Chart.vue ChartInfo  controller error: ');
                    console.log(error.response);
                })



            // Event bus listener
            // This event is received from ChartControl.vue component when price channel update button is clicked
            this.$bus.$on('my-event', ($event) => {
                //console.log('Chart.vue. My event has been triggered. Reload history data', $event) // Output $event parameter
                HistoryBarsLoad(); // Load history data from DB

            });



            // Load history bars and price channel from DB. This functions is called at each new bar or on update price channel
            // Button from ChartControl.vue component
            function HistoryBarsLoad() {
                console.log('Chart.vue. HistoryBarsLoad() function worked');
                axios.get('/historybarsload') // Load history data from BR
                    .then(response => {
                        //console.log('Chart.vue. historybarsload controller response (from function): ');
                        chart1.series[0].setData(response.data['candles'],true); // Candles. true - redraw the series. Candles
                        chart1.series[1].setData(response.data['priceChannelHighValues'],true);// High. Precancel high
                        chart1.series[2].setData(response.data['priceChannelLowValues'],true);// Low. Price channel low
                        chart1.series[3].setData(response.data['longTradeMarkers'],true);// Low. Price channel low
                        chart1.series[4].setData(response.data['shortTradeMarkers'],true);// Low. Price channel low
                    })
                    .catch(error => {
                        console.log('Chart.vue. /historybarsload controller error (from function): ');
                        console.log(error.response);
                    })
            }


            /*
            DELETE THIS CODE! IT IS ALREADY USED!
            Echo.channel('Bush-channel').listen('BushBounce', (e, chart1) => {
                console.log(e.update);
                //var last = this.chart1.series[0].data[chart.series[0].data.length - 1];
                var last = chart1.series[0].data[chart.series[0].data.length - 1];
                last.update({
                    //'open': 1000,
                    'high': e.update["tradeBarHigh"],
                    'low': e.update["tradeBarLow"],
                    'close': e.update["tradePrice"]
                }, true);

                });
           */

        },

    }
</script>

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
        methods:{
            // Load history bars and price channel from DB. This functions is called at each new bar or on update price channel

            HistoryBarsLoad: function(chart1, param) {

                axios.get('/historybarsload') // Load history data from BR
                    .then(response => {

                        // Two types of messages can be received: reload the whole chart or the price channel only
                        // The reason is to make chart reload faster
                        if (param == "reload-price-channel")
                        {
                            console.log('reload-price-channel');
                            chart1.series[1].setData(response.data['priceChannelHighValues'],true);// High. Precancel high
                            chart1.series[2].setData(response.data['priceChannelLowValues'],true);// Low. Price channel low
                            chart1.series[3].setData(response.data['longTradeMarkers'],true);// Low. Price channel low
                            chart1.series[4].setData(response.data['shortTradeMarkers'],true);// Low. Price channel low
                        }

                        // This type of message is called from ChartControl.vue. priceChannelUpdate line 84
                        if (param == "reload-whole-chart") {
                            console.log('reload-whole-chart');
                            chart1.series[0].setData(response.data['candles'], true); // Candles. true - redraw the series. Candles
                            chart1.series[1].setData(response.data['priceChannelHighValues'], true);// High. Precancel high
                            chart1.series[2].setData(response.data['priceChannelLowValues'], true);// Low. Price channel low
                            chart1.series[3].setData(response.data['longTradeMarkers'],true);// Low. Price channel low
                            chart1.series[4].setData(response.data['shortTradeMarkers'],true);// Low. Price channel low
                        }
                    })
                    .catch(error => {
                        console.log('Chart.vue. line 36 /historybarsload function controller error: ');
                        console.log(error.response);
                    })
            }
        },
        created() { // First created then Mounted
        },
        mounted(){ // Then, later mounted

            var chart1 = Highcharts.stockChart('container', {
                chart: {
                    animation: false,
                    renderTo: 'container', // div where the chart will be rendered
                    //height: document.height, // Use window height to set height of the chart
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
                        //data: response.data['priceChannelHighValues'],
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
                        //data: response.data['priceChannelLowValues'],
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
                        //data: response.data['longTradeMarkers'],
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
                        //data: response.data['shortTradeMarkers'],
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
                    }

                ]
            });

            // Load history data from DB and send "reload-whole-chart" parameter
            this.HistoryBarsLoad(chart1, "reload-whole-chart");

            // Websocket event listener. Used only for updating and adding new bars to the chart
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

                    // Add bar to the chart. We arr just a bar where all OLHC values are the same. Later these values are gonna update via websocket listener
                    chart1.series[0].addPoint([e.update["tradeDate"],e.update["tradePrice"],e.update["tradePrice"],e.update["tradePrice"],e.update["tradePrice"]],true, false); // Works good

                    // Add price channel calculated values. Price channel is calculated on each new bar issued. CandleMaker.php line 165
                    axios.get('/historybarsload') // Load history data from BR
                        .then(response => {
                            console.log('reload-price-channel 2');
                            chart1.series[1].setData(response.data['priceChannelHighValues'],true);// High. Precancel high
                            chart1.series[2].setData(response.data['priceChannelLowValues'],true);// Low. Price channel low
                            chart1.series[3].setData(response.data['longTradeMarkers'],true);// Low. Price channel low
                            chart1.series[4].setData(response.data['shortTradeMarkers'],true);// Low. Price channel low
                        })
                        .catch(error => {
                            console.log('Chart.vue. line 200 /historybarsload controller error: ');
                            console.log(error.response);
                        })

                    // Send a message to ChartControl.vue in order to reload calculated net profit and show it at the from
                    this.$bus.$emit('new-bar-added', {});

                } // New bar added


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


        }); // Echo

            // Event bus listener
            // This event is received from ChartControl.vue component when price channel update button is clicked
            this.$bus.$on('my-event', ($event) => {
                //console.log($event.param);
                this.HistoryBarsLoad(chart1, $event.param); // Load history data from DB
            });

        } // Mounted()

    }
</script>

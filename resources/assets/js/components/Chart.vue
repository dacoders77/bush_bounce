<template>
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
                tradingAllowed: '',
            }
        },
        methods:{
            // Load history bars and price channel values from DB.
            // This functions is called at each new bar or on update price channel
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
                            chart1.series[5].setData(response.data['sma'],true);
                        }

                        // This type of message is called from ChartControl.vue. priceChannelUpdate line 84
                        if (param == "reload-whole-chart") {
                            console.log('reload-whole-chart');
                            chart1.series[0].setData(response.data['candles'], true); // Candles. true - redraw the series. Candles
                            chart1.series[1].setData(response.data['priceChannelHighValues'], true);// High. Precancel high
                            chart1.series[2].setData(response.data['priceChannelLowValues'], true);// Low. Price channel low
                            chart1.series[3].setData(response.data['longTradeMarkers'],true);// Low. Price channel low
                            chart1.series[4].setData(response.data['shortTradeMarkers'],true);// Low. Price channel low
                            chart1.series[5].setData(response.data['sma'],true);
                            chart1.series[6].setData(response.data['profitDiagram'],true);
                        }
                    })
                    .catch(error => {
                        console.log('Chart.vue. line 51 /historybarsload function controller error: ');
                        console.log(error.response);
                    })
            },
            ChartBarsUpdate: function (e, chart1){
                let last = chart1.series[0].data[chart1.series[0].data.length - 1];
                last.update({
                    // 'open': is created when new bar is added to the chart
                    'high': e.update['payload']['tradeBarHigh'],
                    'low': e.update['payload']['tradeBarLow'],
                    'close': e.update['payload']['tradePrice']
                }, true);

                // New bar is issued. Flag sent from CandleMaker.php
                if (e.update['payload']['flag']) {
                    console.log('Chart.vue. New bar is added');
                    // Add bar to the chart. We add just a bar (an empty bar) where all OLHC values are the same. Later these values are gonna update via websocket listener
                    chart1.series[0].addPoint([e.update['payload']['tradeDate'],e.update['payload']['tradePrice'],e.update['payload']['tradePrice'],e.update['payload']['tradePrice'],e.update['payload']['tradePrice']],true, false); // Works good
                    // Add price channel calculated values. Price channel is calculated on each new bar issued. CandleMaker.php line 165
                    axios.get('/historybarsload') // Load history data from BR
                        .then(response => {
                            console.log('reload-price-channel 2');
                            chart1.series[1].setData(response.data['priceChannelHighValues'],true);// High. Precancel high
                            chart1.series[2].setData(response.data['priceChannelLowValues'],true);// Low. Price channel low
                            chart1.series[3].setData(response.data['longTradeMarkers'],true);// Low. Price channel low
                            chart1.series[4].setData(response.data['shortTradeMarkers'],true);// Low. Price channel low
                            chart1.series[5].setData(response.data['sma'],true);
                        })
                        .catch(error => {
                            console.log('Chart.vue. line 200 /historybarsload controller error: ');
                            console.log(error.response);
                        })

                    // Send a message to ChartControl.vue in order to reload calculated net profit and show it at the from
                    this.$bus.$emit('new-bar-added', {});
                }
                // Initial start was initiated from the server. php artisan ratchet start
                if (e.update['payload']['serverInitialStart']) {
                    // Load history data from DB and send "reload-whole-chart" parameter
                    alert('Chart.vue RELOAD CHART! line 86'); // We don't get to this code
                    this.HistoryBarsLoad(chart1, "reload-whole-chart");
                }
            },
        },
        created() {
            // First created then Mounted
        },

        mounted(){

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
                    },
                    {
                        name: 'sma',
                        visible: true,
                        enableMouseTracking: true,
                        color: 'green',
                        lineWidth: 2,
                        //data: response.data['priceChannelLowValues'],
                        dataGrouping: {
                            enabled: false
                        }
                    },
                    {
                        name: 'profitDiagram',
                        yAxis: 1, // To which of two y axis this series should be linked
                        //type: 'area',

                        step: true,
                        visible: true,
                        //enableMouseTracking: true,
                        color: 'green',
                        //negativeColor: 'rgba(255, 0, 0, 1)',
                        negativeColor: 'red',
                        //threshold: 2,
                        lineWidth: 1,
                    }
                ]
            });
            // Load history data from DB and send "reload-whole-chart" parameter
            this.HistoryBarsLoad(chart1, "reload-whole-chart");

            // Websocket event listener. Used only for updating and adding new bars to the chart
            Echo.channel('Bush-channel').listen('BushBounce', (e) => {
                // Here access to different bot clones will be performed. We have only one ID for now.
                if(e.update['clientId'] == 12345){
                    if (e.update['messageType'] === 'symbolTickPriceResponse') this.ChartBarsUpdate(e, chart1); // Sent from CandleMaker.php
                    if (e.update['messageType'] === 'error') swal("Failed!", "Error: " + e.update['payload'], "warning");
                    if (e.update['messageType'] === 'info') toast({ type: 'success', title: e.update['payload']});
                    if (e.update['messageType'] === 'reloadChartAfterHistoryLoaded'){
                        Vue.toasted.show("Chart is reloaded!", { type: 'success' });
                        this.HistoryBarsLoad(chart1, "reload-whole-chart");
                    }

                }
            });

            // Event bus listener
            // This event is received from ChartControl.vue component when price channel update button is clicked
            this.$bus.$on('my-event', ($event) => {
                this.HistoryBarsLoad(chart1, $event.param); // Load history data from DB
            });

            // @see https://github.com/shakee93/vue-toasted#actions--fire
            // @see https://fontawesome.com/cheatsheet?from=io
            Vue.toasted.show('hello there, i am a toast !!', { icon : 'check'});

            Vue.toasted.show('Pavel PERMINOV !!', {

                // Options
                action : [
                    // Array of actions
                    {
                        text : 'Cancel',
                        onClick : (e, toastObject) => {
                            toastObject.goAway(1500);
                        },

                    },
                    {
                        text : 'Undo',
                        // Vue router navigation
                        push : {
                            name : 'somewhere',
                            // This will prevent toast from closing
                            dontClose : true
                        }
                    }
                ],
                type: 'error', // 'success', 'info', 'error'
                //fullWidth: true,
                position: 'top-right',
                icon : 'shield-alt',
            } );

            Vue.toasted.show("Holla !!", { type: 'info' });
            //Vue.toasted.show("Holla !!", { type: 'success' });
            //Vue.toasted.show("New message !!", { type: 'success' });
            //Vue.toasted.show("Now type message goes here");

            // Listen to mousemove event of main div in which the StockChart is rendered.
            // This div is located in: VueSideBarMeny.vue
            // When mouse is moved, we wait 3 seconds and then clear all notifications.
            // @see https://stackoverflow.com/questions/51873582/how-do-i-get-the-mousemove-event-to-function-correctly-inside-a-vue-component
            document.getElementById("container").addEventListener('mousemove', function(event){
                //console.log(event.screenX + '-' + event.screenY); // Output coordinates to the console
                setTimeout(function(){
                    let toast = Vue.toasted.clear();
                }, 3000);
            });

        }

    }
</script>

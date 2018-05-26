<template>
    <div>
        <div style="border: thin solid green; padding: 5px">
            Symbol: <input type="text" min="1" max="7" class="form-control" v-model="symbol"/><br>
            Net profit: {{ netProfit }}<br>
            Requested bars: <input type="number" min="1" max="100" class="form-control" v-model="requestBars"/><br>
            Time frame: <input type="number" min="1" max="100" class="form-control" v-model="timeFrame"/><br>
            Commission: {{ commission }}<br>
            Trading allowed: {{ tradingAllowed }}<br>
            Broadcast: {{ broadcastAllowed }}<br>
        </div>
        <div style="border: thin solid darkgray; padding: 5px; margin-top: 5px">
            History:
            <button v-on:click="historyTest" id="history-test">Test</button><br>
            Initial start:
            <button v-on:click="initialStart" id="initial-start">Run</button><br>
            Broadcast:
            <button v-on:click="startBroadcast" id="start-broadcast">Start</button>
            <button v-on:click="stopBroadcast" id="stop-broadcast">Stop</button>
            <br>
            <form v-on:submit.prevent="priceChannelUpdate">
                Price channel / Stop channel period:<br>
                <input type="number" min="1" max="100" class="form-control" v-model="priceChannelPeriod" @input="priceChannelUpdate"/>
                <input type="number" min="1" max="100" class="form-control" v-model="priceChannelPeriod" @input="priceChannelUpdate"/>
                <button>Upd</button>
                <br>
            </form>
        </div>
        <div style="border: thin solid salmon; padding: 5px; margin-top: 5px">
            <!-- Output records to the page -->
            <span v-for="item in items">
            {{ item }}<br>
        </span>
        </div>

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
                timeFrame: '',
                requestBars: '',
                commission: '',
                tradingAllowed: '',
                priceChannelPeriod: null,
                items: '',
                broadcastAllowed: ''
            }
        },
        methods: {
            // Start broadcast button handler
            startBroadcast: function (event) {
                axios.get('/startBroadcast')
                    .then(response => {
                        console.log('ChartControl.vue. startBroadcast controller response: ');
                    })
                    .catch(error => {
                        console.log('ChartControl.vue startBroadcast controller error: ');
                        console.log(error.response);
                    });
                this.broadcastAllowed = 'on';
            },
            // Stop broadcast button handler
            stopBroadcast: function (event) {
                axios.get('/stopBroadcast')
                    .then(response => {
                        //console.log('ChartControl.vue. stopBroadcast controller response: ');
                    })
                    .catch(error => {
                        console.log('ChartControl.vue. stopBroadcast controller error: ');
                        console.log(error.response);
                    });
                this.broadcastAllowed = 'off';
            },
            // Price channel update button click
            // First price channel recalculation started then when the response is received
            // the Event BUS event is generated
            priceChannelUpdate() {
                // Update price channel in DB
                // In this controller price channel recalculation is called automatically
                axios.post('/chartcontrolupdate', this.$data)
                    .then(response => {
                    })
                    .catch(error => {
                        console.log('ChartControl.vue. Form field changed event error:');
                        console.log(error.response);
                    })

                axios.get('/pricechannelcalc' ) // + /this.priceChannelPeriod
                    .then(response => {
                        console.log('ChartControl.vue. pricechannelcalc response');
                        this.$bus.$emit('my-event', {}) // When price channel is recalculated, raise the event
                    })
                    .catch(error => {
                        console.log('ChartControl.vue. pricechannelcalc controller error:');
                        console.log(error.response);
                    })

            },
            // Initial start button handler
            initialStart(){
                console.log('Initial start button clicked');
                this.broadcastAllowed = "off";
                axios.get('/initialstart' )
                    .then(response => {
                        console.log('ChartControl.vue. initialstart response');
                        this.$bus.$emit('my-event', {}) // When history is loaded and price channel recalculated, raise the event
                        this.broadcastAllowed = "on";
                    })
                    .catch(error => {
                        console.log('ChartControl.vue. initialstart controller error:');
                        console.log(error.response);
                    })
                //
            },
            historyTest(){
                // History test button click
            },
            // MMethod
            chartInfo: function() {
                // Chart info values from DB load
                axios.get('/chartinfo')
                    .then(response => {
                        //console.log('ChartControl.vue. ChartInfo controller response: ');
                        this.symbol = response.data['symbol'];
                        this.netProfit = 'not ready yet';
                        this.requestedBars = response.data['request_bars'];
                        this.timeFrame = response.data['time_frame'];
                        this.requestBars = response.data['request_bars'];
                        this.commission = response.data['commission_value'];
                        this.tradingAllowed = response.data['allow_trading'];
                        this.priceChannelPeriod = response.data['price_channel_period'];
                        this.broadcastAllowed = ((response.data['broadcast_stop'] == 1) ? 'off' : 'on');

                        //var isTrueSet = (myValue == 'true');

                    }) // Output returned data by controller
                    .catch(error => {
                        console.log('ChartControl.vue. chartinfo controller error: ');
                        console.log(error.response);
                    });
            }
        },
        mounted() {
            console.log('Component ChartControl.vue mounted.');

            // Console messages output to the page
            // Messages are streamed from php via websocket
            var arr = new Array();
            this.items = arr;
            Echo.channel('Bush-channel').listen('BushBounce', (e) => {
                if (this.items.length < 20) {
                    this.items.push('Price: ' + e.update["tradePrice"] + ' Vol: ' + e.update["tradeVolume"]);
                }
                else {
                    this.items.shift();
                    this.items.push('Price: ' + e.update["tradePrice"] + ' Vol: ' + e.update["tradeVolume"]);
                }
            });

            // Event bus listener
            this.$bus.$on('my-event', ($event) => {
                //console.log('ChartControl.vue. My event has been triggered', $event) // Works good
            });

            // Load chart info values from DB
            this.chartInfo();

        },


    }
</script>

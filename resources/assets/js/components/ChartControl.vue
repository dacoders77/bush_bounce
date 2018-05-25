<template>
    <div>
        <div style="border: thin solid green; padding: 5px">
            Symbol: {{ symbol }}<br>
            Net profit: {{ netProfit }}<br>
            Requested bars: {{ requestedBars }}.<br>
            Commission: {{ commission }}.<br>
            Trading allowed: {{ tradingAllowed }}.<br>
        </div>
        <div style="border: thin solid darkgray; padding: 5px; margin-top: 5px">
            Initial start:
            <button v-on:click="initialstart" id="initial-start">Run</button>
            <br>
            Broadcast:
            <button v-on:click="startbroadcast" id="start-broadcast">Start</button>
            <button v-on:click="stopbroadcast" id="stop-broadcast">Stop</button>
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
                commission: '',
                tradingAllowed: '',
                priceChannelPeriod: null,
                items: ''
            }
        },
        methods: {
            // Initial start button handler
            initialstart: function (event) {
                alert('Truncate table and load new historical data');
                axios.get('/tabletruncate')
                    .then(response => {
                        console.log('ChartControl.vue. Tabletruncate controller response: ');
                    })
                    .catch(error => {
                        console.log('ChartControl.vue Tabletruncate controller error: ');
                        console.log(error.response);
                    })
            },
            // Start laravel Ratchet:start command. Button handler
            startbroadcast: function (event) {
                axios.get('/startbroadcast')
                    .then(response => {
                        console.log('ChartControl.vue. startbroadcast controller response: ');
                    })
                    .catch(error => {
                        console.log('ChartControl.vue startbroadcast controller error: ');
                        console.log(error.response);
                    })
            },
            // Stop broadcast button handler
            stopbroadcast: function (event) {
                axios.get('/stopbroadcast')
                    .then(response => {
                        //console.log('ChartControl.vue. stopbroadcast controller response: ');
                    })
                    .catch(error => {
                        console.log('ChartControl.vue. stopbroadcast controller error: ');
                        console.log(error.response);
                    })
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

            // Chart info values from DB load
            axios.get('/chartinfo')
                .then(response => {
                    //console.log('ChartControl.vue. ChartInfo controller response: ');
                    this.symbol = response.data['symbol'];
                    this.netProfit = 'not ready yet';
                    this.requestedBars = response.data['request_bars'];
                    this.commission = response.data['commission_value'];
                    this.tradingAllowed = response.data['allow_trading'];
                    this.priceChannelPeriod = response.data['price_channel_period'];

                }) // Output returned data by controller
                .catch(error => {
                    console.log('ChartControl.vue. chartinfo controller error: ');
                    console.log(error.response);
                });

        },
    }
</script>

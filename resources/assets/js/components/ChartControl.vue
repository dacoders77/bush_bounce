<template>
    <div class="container">
        Symbol: {{ symbol }}<br>
        Net profit: {{ netProfit }}<br>
        Requested bars: {{ requestedBars }}.<br>
        Commission: {{ commission }}.<br>
        Trading allowed: {{ tradingAllowed }}.<br>
        <button v-on:click="initialstart" id="initial-start">Initial start</button><br>
        <button v-on:click="startbroadcast" id="start-broadcast">Start broadcast</button>
        <button v-on:click="stopbroadcast" id="stop-broadcast">Stop broadcast</button>
        <br>
        Price channel period:
        <form>
            <input class="form-control" v-model="priceChannelPeriod" @input="onControlValueChanged" />
            <br>
        </form>

        <!-- Output records to the page -->
        <span v-for="item in items">
            {{ item }}<br>
        </span>

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
                basketAssets: null,
                items: null,
                priceChannelPeriod: null
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
            // Form fields changed
            onControlValueChanged() {
                //console.log('ChartControl. Form filed value changed event:');
                //console.log(this.$data);
                axios.post('/chartcontrolupdate', this.$data)
                    .then(response => {
                        console.log(response.data);

                        // Load price channel recalculated data
                        this.$bus.$emit('my-event', {}) // WORKS GOOD!

                    })
                    .catch(error => {
                        console.log('ChartControl.vue. Form filed value changed event error:');
                        console.log(error.response);})

            },
        },
        mounted() {
            console.log('Component ChartControl.vue mounted.');
            axios.get('/chartinfo')
                .then(response => {
                    //console.log('ChartControl.vue. ChartInfo controller response: ');
                    this.symbol = response.data['symbol'];
                    this.netProfit = response.data['netProfit'];
                    this.requestedBars = response.data['requestedBars'];
                    this.commission = response.data['commissionValue'];
                    this.tradingAllowed = response.data['tradingAllowed'];
                    this.priceChannelPeriod = response.data['priceChannelPeriod'];

                }) // Output returned data by controller
                .catch(error => {
                    console.log('ChartControl.vue. chartinfo controller error: ');
                    console.log(error.response);
                });



        },
        created() {

            //Event bus test
            this.$bus.$on('my-event', ($event) => {
                console.log('My event has been triggered', $event)
            });

            // Console messages output
            var arr = new Array();
            this.items = arr;

            Echo.channel('Bush-channel').listen('BushBounce', (e) => {
                if (this.items.length < 20)
                {
                    this.items.push('Price: ' + e.update["tradePrice"] + ' Vol: ' + e.update["tradeVolume"]);
                }
                else
                {
                    this.items.shift();
                    this.items.push('Price: ' + e.update["tradePrice"] + ' Vol: ' + e.update["tradeVolume"]);
                }
            });
        },
    }
</script>

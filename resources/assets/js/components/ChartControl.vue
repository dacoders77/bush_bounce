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


            <span v-for="item in items">
                {{ item }}<br>
            </span>


        <!--
        <ul>
            <li v-for="item in items">
                {{ item }}
            </li>
        </ul>
        -->


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
            // http://bounce.kk/public/stopbroadcast
            stopbroadcast: function (event) {
                axios.get('/stopbroadcast')
                    .then(response => {
                        console.log('ChartControl.vue. stopbroadcast controller response: ');
                    })
                    .catch(error => {
                        console.log('ChartControl.vue stopbroadcast controller error: ');
                        console.log(error.response);
                    })
            }
        },
        mounted() {
            console.log('Component ChartControl.vue mounted.');
            axios.get('/chartinfo')
                .then(response => {
                    console.log('ChartControl.vue. ChartInfo controller response: ');
                    //console.log(response['data']);
                    //this.basketName = response.data['basketName'];
                    this.symbol = response.data['symbol'];
                    this.netProfit = response.data['netProfit'];
                    this.requestedBars = response.data['requestedBars'];
                    this.commission = response.data['commissionValue'];
                    this.tradingAllowed = response.data['tradingAllowed'];

                }) // Output returned data by controller
                .catch(error => {
                    console.log('ChartControl.vue ChartInfo controller error: ');
                    console.log(error.response);
                })
        },
        created() {
            // Console messages output
            var arr = new Array();
            this.items = arr;

            Echo.channel('Bush-channel').listen('BushBounce', (e) => {

                //console.log(e.update["tradePrice"]);

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

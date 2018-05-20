<template>
    <div class="container">
        Symbol: {{ symbol }}.
        Net profit: {{ netProfit }}.
        Requested bars: {{ requestedBars }}.
        Commission: {{ commission }}.
        Trading allowed: {{ tradingAllowed }}.
        <button v-on:click="initialstart" id="initial-start">Initial start</button>
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
        methods: {
            // Initial start button handler
            initialstart: function (event) {
                alert('Truncate table and load new historical data');
                axios.get('/tabletruncate')
                    .then(response => {
                        console.log('ChartControl.vue. Tabletruncate controller response: ');
                    }) // Output returned data by controller
                    .catch(error => {
                        console.log('ChartControl.vue Tabletruncate controller error: ');
                        console.log('');
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
        }
    }
</script>

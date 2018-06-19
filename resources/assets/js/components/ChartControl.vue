<template>
    <div>
        <div style="border: thin solid green; padding: 5px">
            Symbol: <input type="text" min="1" max="7" class="form-control" v-model="symbol"/><br>
            Net profit: {{ netProfit }}<br>
            Requested bars (realtime): <input type="number" min="1" max="100" class="form-control" v-model="requestBars"/><br>
            Tst: <input type="date" class="form-control" v-model="historyFrom" style="width: 130px"> - <input type="date" class="form-control" v-model="historyTo" style="width: 130px"><br>
            Time frame: <input type="number" min="1" max="100" class="form-control" v-model="timeFrame"/><br>
            Commission: {{ commission }}<br>
            Trading allowed: {{ tradingAllowed }}<br>
            Broadcast: {{ broadcastAllowed }}<br>
        </div>
        <div style="border: thin solid darkgray; padding: 5px; margin-top: 5px">
            Application mode: <a href="" v-on:click.prevent="modeToggle">{{ appMode }}</a><br>

            <!--
            History:
            <button v-on:click="historyTest" id="history-test">Test</button><br>
            -->
            Initial start:
            <button v-on:click="initialStartButton" id="initial-start">Run</button><br>
            <!--
            Broadcast:
            <button v-on:click="startBroadcast" id="start-broadcast" :disabled="startButtonDisabled">Start</button>
            <button v-on:click="stopBroadcast" id="stop-broadcast" :disabled="stopButtonDisabled">Stop</button>
            <br>
            -->
            <form v-on:submit.prevent="priceChannelUpdate" :disabled="priceChannelFormDisabled">
                Price channel / Stop channel period:<br>
                <input type="number" min="1" max="100" class="form-control" v-model="priceChannelPeriod" :disabled="priceChannelFormDisabled">
                <input type="number" min="1" max="100" class="form-control" v-model="priceChannelPeriod" :disabled="priceChannelFormDisabled">
                <button :disabled="priceChannelFormDisabled">Upd</button>
                <br>
            </form>
        </div>
        <div style="border: thin solid blue; padding: 5px; margin-top: 5px">
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
                netProfit: '',
                requestedBars: '',
                timeFrame: '',
                requestBars: '',
                commission: '',
                tradingAllowed: '',
                priceChannelPeriod: null,
                items: '',
                broadcastAllowed: '',
                appMode: '',
                toggleFlag: true,

                startButtonDisabled: true, // delete it
                stopButtonDisabled: true, // delete it

                priceChannelFormDisabled: '', // Disable upd button and both price channel fields
                historyFrom: '',
                historyTo: ''
            }
        },
        methods: {
            // Methods (functions)

            // Price channel update button click
            // First price channel recalculation started then when the response is received
            // the Event BUS event is generated

            priceChannelUpdate: async function() {
                // Update price channel in DB
                // In this controller price channel recalculation is called automatically

                try {
                    const response = await axios.post('/chartcontrolupdate', this.$data); // Calculate price channel method is called from the inside of this controller
                    const response2 = await axios.get('/profit'); // Calculate profit
                    this.$bus.$emit('my-event', {param : "reload-price-channel"}); // Inform Chart.vue that chart bars must be reloaded

                    const response3 = await axios.get('/chartinfo'); // Show net profit at the from. Is is recalculated each time update buttin is clicked
                    this.netProfit = parseFloat(response3.data['netProfit']).toFixed(2);


                } catch(error) {
                    console.log('ChartControl.vue. line 88. /chartcontrolupdate controller error');
                    console.log(error.response);
                }

            },
            // Initial start button handler
            initialStartButton(){
                console.log('ChartControl.vue. line 106. Initial start button clicked');
                this.initialStartFunction();
            },
            historyTest(){
                // History test button click
            },
            // Switch app mode. Histry - real-time and back
            modeToggle(){
                console.log('ChartControl.vue. line 100. entered modeToggle()');
                this.chartInfo(); // Load chart control values. App mode, request bars etc.
            },

            chartInfo: async function() {
                try {
                    const response = await axios.get('/chartinfo');

                    //console.log('ChartControl.vue. ChartInfo controller response: ');
                    this.symbol = response.data['symbol'];
                    this.netProfit = parseFloat(response.data['netProfit']).toFixed(2);
                    this.requestedBars = response.data['request_bars'];
                    this.timeFrame = response.data['time_frame'];
                    this.requestBars = response.data['request_bars'];
                    this.commission = response.data['commission_value'];
                    this.tradingAllowed = ((response.data['allow_trading'] == '1') ? 'true' : 'false');
                    this.priceChannelPeriod = response.data['price_channel_period'];
                    this.broadcastAllowed = ((response.data['app_mode'] == 'history') ? 'on' : 'off');
                    this.appMode = ((response.data['app_mode'] == 'history') ? 'history' : 'realtime');
                    this.priceChannelFormDisabled = ((response.data['app_mode'] == 'history') ? true : false); // Disable price channel period and upd button
                    this.historyFrom = response.data['history_from'];
                    this.historyTo = response.data['history_to'];

                    // When chart info is loaded go to toggleMode, where the mode is switched
                    this.toggleMode();

                } catch (error) {
                    console.log('ChartControl.vue. line 134. \chartinfo controller error');
                    console.log(error.response);
                }

                if (this.priceChannelPeriod >= this.requestedBars){
                    alert("Price channel period is greater or equal to the quantity of requested bars. No price channel will be plotted! Decrease price channel period or encrease quantity of bars.");
                }
            },

            toggleMode: function(){
                // Determine app_mode, read it from DB. We must read it each time the mode is toggle app mode. From history to real-time and back

                // Entering history mode from realtime
                if (this.appMode == "realtime")
                {

                    console.log("You are entering history testing mode. All previous data will be erased, broadcast will be suspended.");

                    //var conf = confirm("You are entering history testing mode. All previous data will be erased, broadcast will be suspended.");
                    if (true) {
                        this.toggleFlag = false;
                        this.startButtonDisabled = true; // delete it
                        this.stopButtonDisabled = true; // delete it

                        // Put all 3 requests over here. Make ir async
                        this.appMode = "history";

                        this.enterRealTimeMode();

                    }
                }
                //Entering real-time mode from history
                else
                {
                    console.log("You are entering real-time testing mode. All previous data will be erased, the broadcast will be start automatically. Trading should be enabled via setting the tradinf option to true");

                    // Set trading flag to true

                    //var conf = confirm("You are entering real-time testing mode. All previous data will be erased, the broadcast will be start automatically. Trading should be enabled via setting the tradinf option to true");
                    if (true) {
                        this.toggleFlag = true;
                        this.startButtonDisabled = false; // delete it
                        this.stopButtonDisabled = false; // delete it


                        this.initialStartRealTime();
                        this.appMode = "realtime";

                        // Set allow_trading flag in DB to true
                        axios.get('/settradingallowedtrue') // Update app_mode in DB
                            .then(response => {
                            })
                            .catch(error => {
                                console.log('ChartControl.vue. line 189. /settradingallowedfalse. controller error: ');
                                console.log(error.response);
                            });

                        axios.post('/chartcontrolupdate', this.$data) // Update app_mode in DB
                            .then(response => {
                            })
                            .catch(error => {
                                console.log('ChartControl.vue. line 196. /chartcontrolupdate. controller error: ');
                                console.log(error.response);
                            });
                    }
                }
            },



            initialStartFunction: function () {

                console.log('ChartControl.vue. Line 209. Entered Initial start function');
                // Determine from which start (history or realtime) initial start button is clicked
                if (this.appMode == "realtime")
                {
                    console.log('ChartControl.vue. line 178. Entered initial start realtime mode');
                    this.initialStartRealTime(); // Initial start in real-time mode
                }
                else
                {
                    console.log('ChartControl.vue. line 178. Entered initial start history mode');
                    this.initialStartHistory(); // Initial start from history mode
                }
            },


            // Async axios request function
            initialStartRealTime: async function() {
                try {
                    const response = await axios.get('/stopbroadcast'); // 3 requests. One goes after anther
                    const response2 = await axios.get('/initialstart'); // web.php: Truncate table then load new historical data from www.bitfinex.com
                        this.$bus.$emit('my-event', {param : "reload-whole-chart"}) // When history is loaded and price channel recalculated, raise the event. Inform Chart.vue that chart must be reloaded
                    const response3 = await axios.get('/startbroadcast');

                    const response4 = await axios.get('/chartinfo'); // Show net profit at the from. Is is recalculated each time update buttin is clicked
                    this.netProfit = parseFloat(response4.data['netProfit']).toFixed(2);

                } catch (error) {
                    console.log('ChartControl.vue. line 260. Initial realtime start async error: ');
                    console.log(error.response);
                }
            },

            initialStartHistory: async function() {
                try {
                    const response = await axios.get('/historyperiod');
                    const response2 = await axios.get('/profit'); // Calculate profit
                    this.$bus.$emit('my-event', {param : "reload-whole-chart"}) // When history is loaded and price channel recalculated, raise the event. Inform Chart.vue that chart must be reloaded

                    const response4 = await axios.get('/chartinfo'); // Show net profit at the from. Is is recalculated each time update buttin is clicked
                    this.netProfit = parseFloat(response4.data['netProfit']).toFixed(2);

                } catch (error) {
                    console.log('ChartControl.vue. line 261. Initial history start async error: ');
                    console.log(error.response);
                }
            },


            enterRealTimeMode: async function() {

                try {

                    const response = await axios.get('/settradingallowedfalse'); // Set allow_trading flag in DB to false
                    const response1 = await axios.get('/stopbroadcast');
                    const response2 = await axios.get('/historyperiod');
                        this.$bus.$emit('my-event', {param : "reload-whole-chart"}); // Inform Chart.vue that chart bars must be reloaded
                    const response3 = await axios.post('/chartcontrolupdate', this.$data); // Update form data and calculate price channe;

                } catch (error) {
                    console.log('ChartControl.vue. line 252. Enter realtime mode error: ');
                    console.log(error.response);
                }

                /*
                axios.get('/stopbroadcast')
                    .then(response => {
                    })
                    .catch(error => {
                        console.log('ChartControl.vue. line 150. /stopbroadcast controller error:');
                        console.log(error.response);
                    })

                // Load history period
                axios.get('/historyperiod')
                    .then(response => {
                        //console.log('ChartControl.vue. line 121. /historyperiodt controller response ');
                        this.$bus.$emit('my-event', {param : "reload-whole-chart"}); // Inform Chart.vue that chart bars must be reloaded
                    })
                    .catch(error => {
                        console.log('ChartControl.vue. line 161. /historyperiod controller error: ');
                        console.log(error.response);
                    });

                // Update app_mode in DB
                axios.post('/chartcontrolupdate', this.$data)
                    .then(response => {
                    })
                    .catch(error => {
                        console.log('ChartControl.vue. line 170. /chartcontrolupdate. controller error: ');
                        console.log(error.response);
                    });
                */

            }


        },
        created() {

            // Console messages output to the page
            // Messages are streamed from php via websocket
            var arr = new Array();
            this.items = arr;

            Echo.channel('Bush-channel').listen('BushBounce', (e) => {
                if (this.items.length < 15) { // 15 - quantity of rows in quotes window
                    this.items.push('Price: ' + e.update["tradePrice"] + ' Vol: ' + e.update["tradeVolume"]);
                }
                else {
                    this.items.shift();
                    this.items.push('Price: ' + e.update["tradePrice"] + ' Vol: ' + e.update["tradeVolume"]);
                }
            });

            // When a connection error (broadcast stopped and other info messages) occurred in RatchetPawlSocket.php
            Echo.channel('Bush-channel').listen('ConnectionError', (e) => {
                if (this.items.length < 15) { // 15 - quantity of rows in quotes window
                    this.items.push(e.update);
                }
                else {
                    this.items.shift();
                    this.items.push(e.update);
                }
            });

            // Event bus listener
            // This event is fired in Chart.vue when new bar is issued
            this.$bus.$on('new-bar-added', ($event) => {

                // Show net profit at the from. Is is recalculated each time update buttin is clicked
                axios.get('/chartinfo')
                    .then(response => {
                        this.netProfit = parseFloat(response.data['netProfit']).toFixed(2);
                        this.tradingAllowed = ((response.data['allow_trading'] == '1') ? 'true' : 'false');
                    })
                    .catch(error => {
                        console.log('ChartControl.vue. line 344. /chartinfo controller error:');
                        console.log(error.response);
                    })

            });

            // Load chart info values from DB
            //this.chartInfo(); // Wass called as a function
            // THIS CODE IS DOUBLED BECAUSE ASYNC FUNCTION DOES NOT WORK

            axios.get('/chartinfo') // The table will be truncated, history loaded
                .then(response => {
                    //console.log('ChartControl.vue. ChartInfo controller response: ');
                    this.symbol = response.data['symbol'];
                    this.netProfit = parseFloat(response.data['netProfit']).toFixed(2);
                    this.requestedBars = response.data['request_bars'];
                    this.timeFrame = response.data['time_frame'];
                    this.requestBars = response.data['request_bars'];
                    this.commission = response.data['commission_value'];
                    this.tradingAllowed = ((response.data['allow_trading'] == '1') ? 'true' : 'false');
                    this.priceChannelPeriod = response.data['price_channel_period'];
                    this.broadcastAllowed = ((response.data['app_mode'] == 'history') ? 'off' : 'on');
                    this.appMode = ((response.data['app_mode'] == 'history') ? 'history' : 'realtime');
                    this.priceChannelFormDisabled = ((response.data['app_mode'] == 'history') ? true : false); // Disable price channel period and upd button
                    this.historyFrom = response.data['history_from'];
                    this.historyTo = response.data['history_to'];
                })
                .catch(error => {
                    console.log('ChartControl.vue. line 344. /chartinfo controller error:');
                    console.log(error.response);
                })


        },


    }
</script>

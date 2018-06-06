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
            Application mode: <a href="" v-on:click.prevent="modeToggle">{{ modeToggleText }}</a><br>

            <!--
            History:
            <button v-on:click="historyTest" id="history-test">Test</button><br>
            -->
            Initial start:
            <button v-on:click="initialStart" id="initial-start">Run</button><br>
            <!--
            Broadcast:
            <button v-on:click="startBroadcast" id="start-broadcast" :disabled="startButtonDisabled">Start</button>
            <button v-on:click="stopBroadcast" id="stop-broadcast" :disabled="stopButtonDisabled">Stop</button>
            <br>
            -->
            <form v-on:submit.prevent="priceChannelUpdate">
                Price channel / Stop channel period:<br>
                <input type="number" min="1" max="100" class="form-control" v-model="priceChannelPeriod" @input="priceChannelUpdate">
                <input type="number" min="1" max="100" class="form-control" v-model="priceChannelPeriod" @input="priceChannelUpdate">
                <button>Upd</button>
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
                netProfit: 0,
                requestedBars: '',
                timeFrame: '',
                requestBars: '',
                commission: '',
                tradingAllowed: '',
                priceChannelPeriod: null,
                items: '',
                broadcastAllowed: '',
                modeToggleText: '',
                toggleFlag: true,
                startButtonDisabled: true,
                stopButtonDisabled: true,
                historyFrom: '',
                historyTo: ''
            }
        },
        methods: {
            // Delete 'em
            startBroadcast: function (event) {
            },
            //
            stopBroadcast: function (event) {
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
                this.initialStartFunction();
            },
            historyTest(){
                // History test button click
            },
            modeToggle(){
                if (this.toggleFlag)
                {
                    // Enter history testing mode
                    var conf = confirm("You are entering history testing mode. All previous data will be erased, broadcast will be suspended.");
                    if (conf) {
                        this.toggleFlag = false;
                        this.startButtonDisabled = true;
                        this.stopButtonDisabled = true;

                        this.stopBroadCastFunction();
                        //this.initialStartFunction();

                        // call history period controller http://bounce.kk/public/historyperiod
                        axios.get('/historyperiod')
                            .then(response => {
                                console.log('ChartControl.vue. historyperiodt controller response ');
                                // Lets try to call the controller when history finished loading
                                // fire event (load bars)
                                this.$bus.$emit('my-event', {}); // Inform Chart.vue that chart bars must be reloaded
                            })
                            .catch(error => {
                                console.log('ChartControl.vue historyperiod controller error: ');
                                console.log(error.response);
                            });

                        this.modeToggleText = "history testing";
                    }
                }
                else
                {
                    // Enter real-time mode
                    var conf = confirm("You are entering real-time testing mode. All previous data will be erased, the broadcast will be start automatically. Trading should be enabled via setting the tradinf option to true");
                    if (conf) {
                        this.toggleFlag = true;
                        this.startButtonDisabled = false;
                        this.stopButtonDisabled = false;

                        //this.stopBroadCastFunction(); // No need to stop broadcast. In the history mode it was already stopped

                        // Async request
                        this.getUser();

                        this.startBroadCastFunction();
                        this.modeToggleText = "realtime";

                    }
                }
            },
            // Methods
            chartInfo: function() {
                // Chart info values from DB load
                axios.get('/chartinfo')
                    .then(response => {
                        console.log('ChartControl.vue. ChartInfo controller response: ');
                        this.symbol = response.data['symbol'];
                        this.netProfit = 'not ready yet';
                        this.requestedBars = response.data['request_bars'];
                        this.timeFrame = response.data['time_frame'];
                        this.requestBars = response.data['request_bars'];
                        this.commission = response.data['commission_value'];
                        this.tradingAllowed = response.data['allow_trading'];
                        this.priceChannelPeriod = response.data['price_channel_period'];
                        this.broadcastAllowed = ((response.data['broadcast_stop'] == 1) ? 'off' : 'on');
                        this.modeToggleText = ((response.data['broadcast_stop'] == 1) ? 'history testing' : 'realtime');
                        this.historyFrom = response.data['history_from'];
                        this.historyTo = response.data['history_to'];

                        //var isTrueSet = (myValue == 'true');

                    }) // Output returned data by controller
                    .catch(error => {
                        console.log('ChartControl.vue. chartinfo controller error: ');
                        console.log(error.response);
                    });
            },
            getUser: async function() {
                try {
                    const response = await axios.get('/initialstart');
                    console.log('async request');
                    //console.log(response);
                    this.$bus.$emit('my-event', {}) // When history is loaded and price channel recalculated, raise the event. Inform Chart.vue that chart must be reloaded
                } catch (error) {
                    console.log('ChartControl.vue. initialstart controller error:');
                    console.log(error.response);
                }
            },

            initialStartFunction: function () {

                alert('initial start func. this.modeToggleText: ' + this.modeToggleText);
                // There is no controller
                // All code located in web.php
                // 1. Truncate history data table (asset_!
                // 2. Load history App\Classes\History::load()
                // 3. Calculate price channel

                console.log('ChartControl.vue. Initial start function');

                // Determine from which start (history or realtime) initial start button is clicked
                if (this.modeToggleText == "realtime")
                {
                    // Code is moved to modeToggle() function
                }
                else
                {
                    console.log('ChartControl.vue. Entered history mode');

                    // Need to stop broadcasting first

                    axios.get('/historyperiod') // The table will be truncated, history loaded
                        .then(response => {
                            //console.log('ChartControl.vue. historyperiod response');
                            this.$bus.$emit('my-event', {}) // When history is loaded and price channel recalculated, raise the event. Inform Chart.vue that chart must be reloaded
                        })
                        .catch(error => {
                            console.log('ChartControl.vue. historyperiod controller error:');
                            console.log(error.response);
                        })
                }





            },
            startBroadCastFunction(){
                axios.get('/startbroadcast')
                    .then(response => {
                        console.log('ChartControl.vue. startBroadcast controller response: ');
                        this.broadcastAllowed = 'on';
                    })
                    .catch(error => {
                        console.log('ChartControl.vue startBroadcast controller error: ');
                        console.log(error.response);
                    });

            },
            stopBroadCastFunction(){
                axios.get('/stopbroadcast')
                    .then(response => {
                        //console.log('ChartControl.vue. stopBroadcast controller response: ');
                        this.broadcastAllowed = 'off';
                    })
                    .catch(error => {
                        console.log('ChartControl.vue. stopBroadcast controller error: ');
                        console.log(error.response);
                    });

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
            this.$bus.$on('my-event', ($event) => {
                //console.log('ChartControl.vue. My event has been triggered', $event) // Works good
            });

            // Load chart info values from DB
            this.chartInfo();

        },


    }
</script>

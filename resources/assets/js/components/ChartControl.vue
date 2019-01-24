<template>
    <div>

        <b-tabs>
            <b-tab title="Main" active>

                        <table class="table-sm">
                            <tbody>
                            <tr>
                                <td>Symbol/Currency</td>
                                <td>EUR</td>
                                <td>USD</td>
                            </tr>
                            <tr>
                                <td>Volume/Commission:</td>
                                <td>{{ volume }}</td>
                                <td>{{ commission }}</td>
                            </tr>
                            <tr>
                                <td>Longs/Shorts:</td>
                                <td>23</td>
                                <td>18</td>
                            </tr>
                            <tr>
                                <td>Net profit: </td>
                                <td colspan="2" class="text-left">{{ netProfit }}</td>
                            </tr>
                            <tr>
                                <td>Broadcast: </td>
                                <td colspan="2">{{ broadcastAllowed }}</td>
                            </tr>
                            <tr>
                                <td>App mode: </td>
                                <td colspan="2">{{ appMode }}</td>
                            </tr>
                            </tbody>
                        </table>

                        <!--
                        <div class="row">
                            <div class="form-group">
                                <small>Symbol: </small>
                                <input type="text" class="form-control form-control-sm border-0"
                                       style="height: 20px; width:70px" v-model="symbol">
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <small>Volume / Commission: {{ volume }} / {{ commission }}</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <small>Net profit / Broadcast: {{ netProfit }} / {{ broadcastAllowed }}</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <small>Requested bars(real-time) / Timeframe: </small>
                                <input type="text" class="form-control form-control-sm border-0"
                                       style="height:12px; width:35px; font-size: 12px; padding: 0px; margin: 0px" v-model="requestBars"> /
                                <input type="text" class="form-control form-control-sm border-0"
                                       style="height:14px; width:35px" v-model="timeFrame">
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <small>Tst: </small>
                                <input type="date" class="form-control form-control-sm"
                                       style="height: 30px; width: 140px" v-model="historyFrom">
                            </div>
                            <div>-</div>
                            <div class="form-group" style="background-color: pink;">
                                <input type="date" class="form-control form-control-sm"
                                       style="height: 30px; width:140px" v-model="historyTo">
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <small>Application mode:
                                <a href="" v-on:click.prevent="modeToggle">{{ appMode }}</a>
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <small>
                                <button v-on:click="initialStartButton" id="initial-start" style="height: 25px; width:140px">Initial run</button>
                                </small>
                            </div>
                        </div>

                        -->




                <!--
                <form v-on:submit.prevent="priceChannelUpdate" :disabled="priceChannelFormDisabled" class="form-inline">
                    <small>
                        Price channel / Stop channel period:<br>
                        <input type="number" min="1" max="100" class="form-control" v-model="priceChannelPeriod"
                               :disabled="priceChannelFormDisabled" style="height: 25px; width:50px">
                        <input type="number" min="1" max="100" class="form-control" v-model="priceChannelPeriod"
                               :disabled="priceChannelFormDisabled" style="height: 25px; width:50px">
                        <button :disabled="priceChannelFormDisabled">Params update</button>
                    </small>
                </form>
                -->


                <div style="padding: 5px; margin-top: 5px">

                    <b-tabs>
                        <b-tab title="Trades" active>
                            <!-- Output trades to the tab in the realtime -->
                            <span v-for="item in items">
                                <small>
                                    {{ item }}<br>
                                </small>
                            </span>
                        </b-tab>
                        <b-tab title="Orders trace" >

                            <!-- Output limit order statuses to the tab -->
                            <span v-for="limitOrderStatus in limitOrderStatuses">
                              <small>
                                  {{ limitOrderStatus }}<br>
                              </small>
                            </span>

                        </b-tab>
                    </b-tabs>

                </div>

            </b-tab>


            <b-tab title="Trades">
                <br>Trades table
            </b-tab>
            <b-tab title="Orders">
                <br>Orders table
            </b-tab>
        </b-tabs>

    </div>
</template>

<style>
    th, td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    table {
        border-collapse: collapse;
        width: 100%;
    }
</style>

<script>
    export default {
        props: [],
        data() {
            return {
                symbol: '',
                volume: '',
                netProfit: '',
                requestedBars: '',
                timeFrame: '',
                requestBars: '',
                commission: '',
                tradingAllowed: '',
                priceChannelPeriod: null,
                items: '', // Trades array. Used for real-time feed output to the window (tab)
                limitOrderStatuses: '', // Limit order events. When limit order is moved, filled, canceled etc. Sent from ccxt.php
                broadcastAllowed: '',
                appMode: '',
                toggleFlag: true,
                priceChannelFormDisabled: false, // Disable upd button and both price channel fields
                historyFrom: '',
                historyTo: ''
            }
        },
        methods: {
            // Methods (functions)

            // Price channel update button click
            // First price channel recalculation started then when the response is received
            // the Event BUS event is generated

            priceChannelUpdate: async function () {
                // Update price channel in DB
                // In this controller price channel recalculation is called automatically

                try {
                    const response = await axios.post('/chartcontrolupdate', this.$data); // Calculate price channel method is called from the inside of this controller
                    const response2 = await axios.get('/profit'); // Calculate profit
                    this.$bus.$emit('my-event', {param: "reload-price-channel"}); // Inform Chart.vue that chart bars must be reloaded

                    const response3 = await axios.get('/chartinfo'); // Show net profit at the from. Is is recalculated each time update buttin is clicked
                    this.netProfit = parseFloat(response3.data['netProfit']).toFixed(4);

                } catch (error) {
                    console.log('ChartControl.vue. line 88. /chartcontrolupdate controller error');
                    console.log(error.response);
                }

            },
            // Initial start button handler
            initialStartButton() {
                console.log('ChartControl.vue. line 106. Initial start button clicked');
                this.initialStartFunction();
            },
            historyTest() {
                // History test button click
            },
            // Switch app mode. From history mode to real-time and back
            modeToggle() {
                console.log('ChartControl.vue. line 211. Entered modeToggle()');
                this.chartInfo(); // Load chart control values. App mode, request bars etc.
            },

            chartInfo: async function () {
                try {
                    const response = await axios.get('/chartinfo');

                    //console.log('ChartControl.vue. ChartInfo controller response: ');
                    this.symbol = response.data['symbol'];
                    this.volume = response.data['volume'];
                    this.netProfit = parseFloat(response.data['netProfit']).toFixed(4);
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

                if (this.priceChannelPeriod >= this.requestedBars) {
                    alert("Price channel period is greater or equal to the quantity of requested bars. No price channel will be plotted! Decrease price channel period or encrease quantity of bars.");
                }
            },

            toggleMode: function () {
                // Determine app_mode, read it from DB. We must read it each time the mode is toggle app mode.
                // From history to real-time and back

                // Entering history mode from realtime
                if (this.appMode == "realtime") {

                    console.log("You are entering history testing mode. All previous data will be erased, broadcast will be suspended.");

                    //var conf = confirm("You are entering history testing mode. All previous data will be erased, broadcast will be suspended.");
                    if (true) {
                        this.toggleFlag = false;

                        // Put all 3 requests over here. Make ir async
                        this.appMode = "history";
                        this.enterRealTimeMode();
                    }
                }

                //Entering real-time mode from history
                else {
                    console.log("You are entering real-time testing mode. All previous data will be erased, the broadcast will be start automatically. Trading should be enabled via setting the tradinf option to true");

                    // Set trading flag to true
                    //var conf = confirm("You are entering real-time testing mode. All previous data will be erased, the broadcast will be start automatically. Trading should be enabled via setting the tradinf option to true");
                    if (true) {
                        this.toggleFlag = true;
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
                if (this.appMode == "realtime") {
                    console.log('ChartControl.vue. line 304. Entered initial start realtime mode');
                    this.initialStartRealTime(); // Initial start in real-time mode
                }
                else {
                    console.log('ChartControl.vue. line 308. Entered initial start history mode');
                    this.initialStartHistory(); // Initial start from history mode
                }
            },


            // Async axios request function
            initialStartRealTime: async function () {
                try {
                    const response = await axios.get('/stopbroadcast'); // 3 requests. One goes after anther
                    const response2 = await axios.get('/initialstart'); // web.php: Truncate table then load new historical data from www.bitfinex.com
                    this.$bus.$emit('my-event', {param: "reload-whole-chart"}) // When history is loaded and price channel recalculated, raise the event. Inform Chart.vue that chart must be reloaded
                    const response3 = await axios.get('/startbroadcast');

                    const response4 = await axios.get('/chartinfo'); // Show net profit at the from. Is is recalculated each time update buttin is clicked
                    this.netProfit = parseFloat(response4.data['netProfit']).toFixed(4);

                } catch (error) {
                    console.log('ChartControl.vue. line 260. Initial realtime start async error: ');
                    console.log(error.response);
                }
            },

            initialStartHistory: async function () {
                try {
                    const response = await axios.get('/historyperiod');
                    const response2 = await axios.get('/profit'); // Backtest and profit calculation
                    this.$bus.$emit('my-event', {param: "reload-whole-chart"}) // When history is loaded and price channel recalculated, raise the event. Inform Chart.vue that chart must be reloaded

                    const response4 = await axios.get('/chartinfo'); // Show net profit on the from. Is is recalculated each time update buttin is clicked
                    this.netProfit = parseFloat(response4.data['netProfit']).toFixed(4);

                } catch (error) {
                    console.log('ChartControl.vue. line 261. Initial history start async error: ');
                    console.log(error.response);
                }
            },


            enterRealTimeMode: async function () {

                try {
                    const response = await axios.get('/settradingallowedfalse'); // Set allow_trading flag in DB to false
                    const response1 = await axios.get('/stopbroadcast');
                    const response2 = await axios.get('/historyperiod');
                    this.$bus.$emit('my-event', {param: "reload-whole-chart"}); // Inform Chart.vue that chart bars must be reloaded
                    const response3 = await axios.post('/chartcontrolupdate', this.$data); // Update form data and calculate price channe;

                } catch (error) {
                    console.log('ChartControl.vue. line 252. Enter realtime mode error: ');
                    console.log(error.response);
                }
            }
        },
        created() {

            // Console messages output to the page
            // Messages are streamed from php via websocket
            var arr = new Array();
            this.items = arr;

            this.limitOrderStatuses = new Array();

            Echo.channel('Bush-channel').listen('BushBounce', (e) => {
                var tickDate = new Date(e.update["tradeDate"]);
                if (this.items.length < 11) { // 15 - quantity of rows in quotes window
                    this.items.push('Price: ' + e.update["tradePrice"] + ' Vol: ' + e.update["tradeVolume"] + " | " + tickDate.getHours() + ":" + tickDate.getMinutes() + ":" + tickDate.getSeconds());
                }
                else {
                    this.items.shift();
                    this.items.push('Price: ' + e.update["tradePrice"] + ' Vol: ' + e.update["tradeVolume"] + " | " + tickDate.getHours() + ":" + tickDate.getMinutes() + ":" + tickDate.getSeconds());
                }
            });

            // When a connection error (broadcast stopped and other info messages) occurred in RatchetPawlSocket.php
            Echo.channel('Bush-channel').listen('ConnectionError', (e) => {
                if (this.items.length < 11) { // 11 - quantity of rows in quotes window
                    this.items.push(e.update);
                }
                else {
                    this.items.shift();
                    this.items.push(e.update);
                }
            });

            // Limit order status events. Sent from ccxt.php where limit orders are moved and handled
            Echo.channel('Bush-channel').listen('LimitOrderTrace', (e) => {
                if (this.limitOrderStatuses.length < 11) { // 11 - quantity of rows in Order trace window
                    this.limitOrderStatuses.push(e.update);
                }
                else {
                    this.limitOrderStatuses.shift();
                    this.limitOrderStatuses.push(e.update);
                }

            });

            // Event bus listener
            // This event is fired in Chart.vue when new bar is issued
            this.$bus.$on('new-bar-added', ($event) => {

                // Show net profit at the from. Is is recalculated each time update buttin is clicked
                axios.get('/chartinfo')
                    .then(response => {
                        this.netProfit = parseFloat(response.data['netProfit']).toFixed(4);
                        this.tradingAllowed = ((response.data['allow_trading'] == '1') ? 'true' : 'false');
                    })
                    .catch(error => {
                        console.log('ChartControl.vue. line 344. /chartinfo controller error:');
                        console.log(error.response);
                    })

            });

            // Load chart info values from DB
            // this.chartInfo(); // Was called as a function
            // THIS CODE IS DOUBLED BECAUSE ASYNC FUNCTION DOES NOT WORK

            axios.get('/chartinfo') // The table will be truncated, history loaded
                .then(response => {
                    //console.log('ChartControl.vue. ChartInfo controller response: ');
                    this.symbol = response.data['symbol'];
                    this.volume = response.data['volume'];
                    this.netProfit = parseFloat(response.data['netProfit']).toFixed(4);
                    this.requestedBars = response.data['request_bars'];
                    this.timeFrame = response.data['time_frame'];
                    this.requestBars = response.data['request_bars'];
                    this.commission = response.data['commission_value'];
                    this.tradingAllowed = ((response.data['allow_trading'] == '1') ? 'true' : 'false');
                    this.priceChannelPeriod = response.data['price_channel_period'];
                    this.broadcastAllowed = ((response.data['app_mode'] == 'history') ? 'off' : 'on');
                    this.appMode = ((response.data['app_mode'] == 'history') ? 'history' : 'realtime');
                    this.priceChannelFormDisabled = ((response.data['app_mode'] == 'history') ? false : true); // Disable price channel period and upd button
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

<!--
We have to link this css file only for this component. It should be linked in bootstrap.js but in that case it
override other laravel bootstrap styles and default login for breaks down.
https://bootstrap-vue.js.org/docs/components/tabs/
-->
<style src="bootstrap/dist/css/bootstrap.css"></style>
<style></style>





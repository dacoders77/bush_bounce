<template>
    <div class="container">
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
        mounted() {
            console.log('Component Chart.vue mounted');
            axios.get('/historybarsload')
                .then(response => {
                    //console.log('Chart.vue. Historybarsload controller response: ');
                    var chart1; // globally available
                    chart1 = Highcharts.stockChart('container', {
                        chart: {
                            animation: false,
                            renderTo: 'container' // DIV where the chart will be rendered
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
                            data: response.data['candles'],
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
                                data: '',
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
                                data: '',
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
                                data: '',
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
                                data: '',
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
                            }]
                    });

                }) // Output returned data by controller
                .catch(error => {
                    console.log('Chart.vue ChartInfo  controller error: ');
                    console.log(error.response);
                })

        },

        created() {
            Echo.channel('Bush-channel').listen('BushBounce', (e) => {
                alert(e.updte);
                });
        },

    }
</script>

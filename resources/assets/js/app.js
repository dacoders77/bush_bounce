/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

Object.defineProperties(Vue.prototype, { // Attached bus
    $bus: {
        get: function () {
            return EventBus
        }
    },
});

const EventBus = new Vue({
    created(){
        this.$on('my-event', this.handleMyEvent)
    },
    methods:{
        handleMyEvent ($event) {
            console.log('app.js. My event caught in global event bus', $event)
        }
    }
})

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

/*
Vue.component('example-component', require('./components/ExampleComponent.vue'));
const app = new Vue({
    el: '#app'
});
*/

// Chart control component
Vue.component('chart-control', require('./components/ChartControl.vue'));
// Chart component
Vue.component('chart', require('./components/Chart.vue'));

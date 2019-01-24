/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

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

/**
 * Sweet alert
 *
 * @see https://sweetalert2.github.io/#download
 * @see https://www.youtube.com/watch?v=qJt6EfbQu6E
 */
import swal from 'sweetalert2' // Pop-up
window.swal = swal;

const toast = swal.mixin({ // Corner notification. Custom swall
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: 3000
});
window.toast = toast;


// register the plugin on vue
// @see https://github.com/shakee93/vue-toasted#actions--fire
import Toasted from 'vue-toasted';
Vue.use(Toasted, {
    // Options:
    iconPack : 'fontawesome' // set your iconPack, defaults to material. material|fontawesome|custom-class
})




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
            //console.log('app.js. My event caught in global event bus', $event) // Works good
        }
    }
})


Vue.component('chart-control', require('./components/ChartControl.vue'));
Vue.component('chart', require('./components/Chart.vue'));
Vue.component('vue-sidebar-menu', require('./components/VueSidebarMenu.vue'));

const app = new Vue({
    el: '#vue-app', // This #.. covers the whole code
        components: {
            //SidebarMenu // Register your component
        }
});





















/*
// Chart control component
Vue.component('chart-control', require('./components/ChartControl.vue'));
const app2 = new Vue({
    el: '#chart-control'
});

// Chart component
Vue.component('chart', require('./components/Chart.vue'));
const app3 = new Vue({
    el: '#chart'
});


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
*/


//Event bus component (http://vuetips.com/global-event-bus)
//Vue.component('event-bus', require('./components/EventBus.vue'));







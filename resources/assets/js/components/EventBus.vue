<template>

</template>


<script>

    export default {
        name: 'my-component',
        created () {
            this.$bus.$on('my-event', ($event) => {
                console.log('My event has been triggered', $event)
            })
        },
        mounted() {
            console.log('EventBus.vue. Component mounted');
        }
    }


</script>
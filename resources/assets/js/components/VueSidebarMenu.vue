<template>
    <sidebar-menu :menu="menu" :collapsed="collapsed" :theme="theme" />
</template>

<script>
    export default {
        data() {
            return {
                menu: [
                    {
                        header: true,
                        title: 'Bush Channel',
                    },
                    {
                        href: '/',
                        title: 'Dashboard',
                        icon: 'fa fa-user',
                        disabled: false
                    },
                    {
                        title: 'Charts',
                        icon: 'fa fa-chart-area',
                        child: [
                            {
                                href: '/charts/sublink',
                                title: 'Sub Link',
                            }
                        ]
                    },
                ],
                collapsed: true,
                theme: 'white-theme'
            }
        },

        mounted() {
            console.log('VueSidebarMenu component mounted.')
        }
    }
</script>

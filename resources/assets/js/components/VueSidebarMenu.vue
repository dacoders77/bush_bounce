<template>
    <div>
        <sidebar-menu :menu="menu" :collapsed="collapsed" :theme="theme" :width="width" style="z-index: 1"></sidebar-menu>
        <div style="border-style: solid; border-color: green; border-width:0; margin-left:50px; height: 99.5vh" id="container">
            <chart></chart>
        </div>
    </div>
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
                        icon: 'fas fa-chalkboard-teacher',
                        disabled: false
                    },
                    {
                        title: 'Charts',
                        icon: 'fa fa-chart-area',
                        child: [
                            {
                                href: '/charts/sublink',
                                icon: 'fa fa-chart-area',
                                title: 'Clone 1',
                            },
                            {
                                href: '/charts/sublink',
                                icon: 'fa fa-chart-area',
                                title: 'Clone 2',
                            },
                            {
                                href: '/charts/sublink',
                                icon: 'fa fa-chart-area',
                                title: 'Clone 3',
                            },
                        ]
                    },
                    {
                        href: '/',
                        title: 'Docker',
                        icon: 'fab fa-docker',
                        disabled: false
                    },
                    {
                        href: '/',
                        title: 'Logs',
                        icon: 'fas fa-bug',
                        disabled: false
                    },
                    {
                        href: '/',
                        title: 'Logout',
                        icon: 'fas fa-sign-out-alt',
                        disabled: false
                    },

                ],
                collapsed: true,
                theme: 'default-theme', // 'white-theme' 'default-theme',
                width: '200px'
            }
        },
        mounted() {
            //
        }
    }
</script>


<style lang="scss">
    $fa-font-path : "~@fortawesome/fontawesome-free/webfonts";
    @import "~@fortawesome/fontawesome-free/scss/fontawesome.scss";
    @import "~@fortawesome/fontawesome-free/scss/solid.scss";
    @import "~@fortawesome/fontawesome-free/scss/brands.scss";
    @import "~@fortawesome/fontawesome-free/scss/regular.scss";
</style>
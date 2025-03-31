<script setup>

import MenuBar from 'primevue/menubar'

const store = useStore()

const user = computed(() => {
    return store.getters.getUser
})

console.log('User in NavBar:', user.value)

const items = computed(() => [
    {
        label: 'Dashboard',
        route: '/',
        visible: () => user.value.canAddCurations(),
    },
    {
        label: 'Curations',
        route: '/curations',
    },
    {
        label: 'Working Groups',
        route: '/working-groups',
    },
    {
        label: 'Curation Export',
        route: '/curations/export',
    },
    {
        label: 'Bulk Lookup',
        items: [
            { label: 'Curation Lookup', route: '/bulk-lookup/curations' },
            { label: 'Gene/Phenotype Lookup', route: '/bulk-lookup/genes' }
        ]
    },
    {
        label: '|',
        separator: true,
        style: { 'flex-grow': 1 },
    },
    {
        label: `${user.value.user.name || 'Login'}`,
        items: [
            { label: 'Curation Export', route: '/curations/export' },
            { label: 'Bulk Upload', route: '/bulk-uploads', visible: () => user.value.hasAnyRole('programmer', 'admin', 'coordinator') },
            { label: 'Admin', route: '/admin', visible: () => user.value.hasAnyRole('programmer', 'admin') }, 
            { label: 'Logs', route: '/admin/logs', visible: () => user.value.hasRole('programmer') },
            { label: 'SOP', url: '/files/SOP_V1.pdf', target: '_blank' },
            // FIXME: logout
        ]
    }
])

</script>

<template>
    <div class="flex">
    <nav class="container navbar navbar-default navbar-expand-md navbar-light navbar-laravel">
        <MenuBar :model="items" style="flex-basis: 100%">
            <template #start>
                <a class="navbar-brand" href="/#/">
                    <img src="/images/clingen_logo_75w.png">
                </a>
            </template>
            <template #item="{ item, props, hasSubmenu }">
                <span v-if="!item.hidden">
                    <router-link v-if="item.route" v-slot="{ href, navigate }" :to="item.route" custom>
                        <a :href="href" v-bind="props.action" @click="navigate">
                            <span :class="item.icon" />
                            <span>{{ item.label }}</span>
                        </a>
                    </router-link>
                    <a v-else :href="item.url" :target="item.target" v-bind="props.action">
                        <span :class="item.icon" />
                        <span>{{ item.label }}</span>
                        <span v-if="hasSubmenu" class="pi pi-fw pi-angle-down" />
                    </a>
                </span>
            </template>
            <template #end>
                <a class="navbar-brand" href="/#/">
                    <img src="/images/clingen_logo_75w.png">
                </a>
            </template>
            <!-- FIXME: include help modal -->
        </MenuBar>
    </nav>
    </div>
</template>

<style scoped>
nav {
    display: flex;
    width: 100%;
    flex-basis: 100%;
}

:deep(.p-menubar) {
    flex-grow: 1;
    flex-basis: 100%;
}

:deep(.p-menubar-root-list) {
    display: flex;
    flex-grow: 1;
    flex-basis: 100%;
}
</style>
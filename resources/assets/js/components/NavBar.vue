<script setup>

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
    <div class="q-pa-md">
        <q-toolbar class="bg-white text-primary">
            <a class="navbar-brand" href="/#/">
                <img src="/images/clingen_logo_75w.png">
            </a>
            <q-btn flat to="/" label="Dashboard" />
            <q-btn flat to="/curations" label="Curations" />
            <q-btn flat to="/working-groups" label="Working Groups" />
            <q-btn flat to="/curations/export" label="Curation Export" />
            <q-btn flat to="/bulk-lookup/curations" label="Bulk Lookup" />
            <q-btn-dropdown label="Bulk Lookup" class="q-ml-xs">
                <q-list>
                    <q-item clickable to="/bulk-lookup/curations">
                        <q-item-section>Curations</q-item-section>
                    </q-item>
                    <q-item clickable to="/bulk-lookup/genes">
                        <q-item-section>Gene/Phenotype Lookup</q-item-section>
                    </q-item>
                </q-list>
            </q-btn-dropdown>
            <q-space />
            <a class="navbar-brand" href="/#/">
                <img src="/images/clingen_logo_75w.png">
            </a>
        </q-toolbar>
    </div>
</template>

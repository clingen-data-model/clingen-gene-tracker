<template>
    <div class="row">
        <aside class="col-md-3 col-lg-2 mb-4">
            <h1 class="h4">Administration</h1>
            <nav class="nav flex-column" aria-label="Administration">
                <router-link class="nav-link" :to="{ name: 'admin-index' }">Overview</router-link>
                <router-link
                    v-if="user.hasPermission('list curation-types')"
                    class="nav-link"
                    :to="{ name: 'admin-curation-types' }"
                >
                    Curation Types
                </router-link>
                <router-link
                    v-if="user.hasPermission('list rationales')"
                    class="nav-link"
                    :to="{ name: 'admin-rationales' }"
                >
                    Rationales
                </router-link>
                <router-link
                    v-if="user.hasPermission('list curation-statuses')"
                    class="nav-link"
                    :to="{ name: 'admin-curation-statuses' }"
                >
                    Curation Statuses
                </router-link>
                <router-link class="nav-link" :to="{ name: 'admin-upload-categories' }">
                    Upload Categories
                </router-link>
                <router-link
                    v-if="user.hasPermission('list mois')"
                    class="nav-link"
                    :to="{ name: 'admin-mois' }"
                >
                    Modes of Inheritance
                </router-link>
                <router-link
                    v-if="user.hasPermission('list working-groups')"
                    class="nav-link"
                    :to="{ name: 'admin-working-groups' }"
                >
                    Working Groups
                </router-link>
                <router-link
                    v-if="user.hasPermission('list expert-panels')"
                    class="nav-link"
                    :to="{ name: 'admin-expert-panels' }"
                >
                    Expert Panels
                </router-link>
            </nav>
        </aside>
        <section class="col-md-9 col-lg-10">
            <router-view />
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'

const store = useStore()
const user = computed(() => store.getters.getUser)
</script>

import { createRouter, createWebHistory } from 'vue-router'
import store from './store/index'

const user = store.getters.getUser;

const routes = [{
        path: '',
        component: () => import('@/components/UserDashboard.vue'),
        beforeEnter: (to, from, next) => {
            if (!user.canAddCurations() && !user.isCurator()) {
                next({ path: '/curations' })
                return;
            }
            next()
        }
    },
    {
        path: '/loginForm',
        component: () => import('@/components/LoginForm.vue'),
    },
    {
        path: '/working-groups',
        component: () => import('@/components/WorkingGroups/Index.vue'),
        children: [{
                path: '',
                component: () => import ('@/components/WorkingGroups/List.vue'),
            },
            {
                path: ':id',
                component: () => import ('@/components/WorkingGroups/Show.vue'),
                props: true
            }
        ],
    },
    {
        path: '/curations',
        component: () => import ('@/components/Curations/List.vue'),
    },
    {
        path: '/curations/create',
        component: () => import ('@/components/Curations/Create.vue'),
        name: 'curations-create',
        beforeEnter: (to, from, next) => {
            if (!user.canAddCurations()) {
                next({ path: '/curations' })
                return;
            }

            next()
        },
    },
    {
        path: '/curations/export',
        component: () => import ('@/components/Curations/ExportForm.vue'),
    },
    {
        path: '/curations/:id',
        component: () => import ('@/components/Curations/Show.vue'),
        props: true,
        name: 'curations-show'
    },
    {
        path: '/curations/:id/edit',
        component: () => import ('@/components/Curations/Edit.vue'),
        props: true,
        name: 'curations-edit',
        // beforeEnter: (to, from, next) => {
        //     console.log(store);
        //     if (!user.canUpdateCurations()) {
        //         next(from)
        //         return;
        //     }
        //     next()
        // }
    },
    {
        name: 'GeneBulkLookup',
        path: '/bulk-lookup/genes',
        component: () => import ('@/components/GeneBulkLookup.vue'),
    },
    {
        name: 'BulkCurationLookup',
        path: '/bulk-lookup/curations',
        component: () => import ('@/components/Curations/BulkLookup.vue'),
    },
    {
        name: 'BulkLookup',
        path: '/bulk-lookup',
        redirect: {name: 'BulkCurationLookup'},
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router
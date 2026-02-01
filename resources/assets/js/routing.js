import { createRouter, createWebHashHistory } from 'vue-router'
import { useAppStore } from './stores/app'

const Curations = () => import('./components/Curations/Curation.vue')
const CurationCreate = () => import('./components/Curations/Create.vue')
const CurationEdit = () => import('./components/Curations/Edit.vue')
const CurationShow = () => import('./components/Curations/Show.vue')
const CurationList = () => import('./components/Curations/List.vue')
const CriteriaOverview = () => import('./components/CriteriaOverview.vue')
const WorkingGroups = () => import('./components/WorkingGroups/Index.vue')
const GroupList = () => import('./components/WorkingGroups/List.vue')
const GroupShow = () => import('./components/WorkingGroups/Show.vue')
const UserDashboard = () => import('./components/UserDashboard.vue')
const CurationExportForm = () => import('./components/Curations/ExportForm.vue')
const BulkLookup = () => import('./components/Curations/BulkLookup.vue')
const GeneBulkLookup = () => import('./components/GeneBulkLookup.vue')

const routes = [
    {
        path: '',
        component: UserDashboard,
        beforeEnter: (to, from, next) => {
            const appStore = useAppStore()
            const user = appStore.getUser
            if (!user.canAddCurations() && !user.isCurator()) {
                next({ path: '/curations' })
                return
            }
            next()
        },
    },
    {
        path: '/working-groups',
        component: WorkingGroups,
        children: [
            {
                path: '',
                component: GroupList,
            },
            {
                path: ':id',
                component: GroupShow,
                props: true,
            },
        ],
    },
    {
        path: '/curations',
        component: Curations,
        children: [
            {
                path: '',
                component: CurationList,
                name: 'curations-index',
            },
            {
                path: 'create',
                component: CurationCreate,
                name: 'curations-create',
                beforeEnter: (to, from, next) => {
                    const appStore = useAppStore()
                    const user = appStore.getUser
                    if (!user.canAddCurations()) {
                        next({ path: '/curations' })
                        return
                    }
                    next()
                },
            },
            {
                path: 'export',
                component: CurationExportForm,
            },
            {
                path: ':id',
                component: CurationShow,
                props: true,
                name: 'curations-show',
            },
            {
                path: ':id/edit',
                component: CurationEdit,
                props: true,
                name: 'curations-edit',
            },
        ],
    },
    {
        name: 'GeneBulkLookup',
        path: '/bulk-lookup/genes',
        component: GeneBulkLookup,
    },
    {
        name: 'BulkCurationLookup',
        path: '/bulk-lookup/curations',
        component: BulkLookup,
    },
    {
        name: 'BulkLookup',
        path: '/bulk-lookup',
        redirect: { name: 'BulkCurationLookup' },
    },
]

const router = createRouter({
    history: createWebHashHistory(),
    routes,
})

export default router

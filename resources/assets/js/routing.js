import Vue from 'vue'
import VueRouter from 'vue-router'
import store from './store/index'

const Curations = () =>
    import ( /* webpackChunkName: "curations" */ './components/Curations/Curation.vue')
const CurationCreate = () =>
    import ( /* webpackChunkName: "CurationCreate" */ './components/Curations/Create.vue')
const CurationEdit = () =>
    import ( /* webpackChunkName: "CurationEdit" */ './components/Curations/Edit.vue')
const CurationShow = () =>
    import ( /* webpackChunkName: "CurationShow" */ './components/Curations/Show.vue')
const CurationList = () =>
    import ( /* webpackChunkName: "CurationList" */ './components/Curations/List.vue')
const CriteriaOverview = () =>
    import ( /* webpackChunkName: "CriteriaOverview" */ './components/CriteriaOverview.vue')
const WorkingGroups = () =>
    import ( /* webpackChunkName: "WorkingGroups" */ './components/WorkingGroups/Index.vue')
const GroupList = () =>
    import ( /* webpackChunkName: "GroupList" */ './components/WorkingGroups/List.vue')
const GroupShow = () =>
    import ( /* webpackChunkName: "GroupShow" */ './components/WorkingGroups/Show.vue')
const UserDashboard = () =>
    import ( /* webpackChunkName: "UserDashboard" */ './components/UserDashboard.vue')
const CurationExportForm = () =>
    import ( /* webpackChunkName: "CurationExportForm" */ './components/Curations/ExportForm.vue')
const BulkLookup = () =>
    import ( /* webpackChunkName: "BulkLookup" */ './components/Curations/BulkLookup.vue')
const GeneBulkLookup = () =>
    import ( /* webpackChunkName: "GeneBulkLookup" */ './components/GeneBulkLookup.vue')

Vue.use(VueRouter)

const user = store.getters.getUser;

const routes = [{
        path: '',
        component: UserDashboard,
        beforeEnter: (to, from, next) => {
            if (!user.canAddCurations() && !user.isCurator()) {
                next({ path: '/curations' })
                return;
            }
            next()
        }
    },
    {
        path: '/working-groups',
        component: WorkingGroups,
        children: [{
                path: '',
                component: GroupList
            },
            {
                path: ':id',
                component: GroupShow,
                props: true
            }
        ],
        // beforeEnter: (to, from, next) => {
        //     if (!user.hasPermission('list working-groups')) {
        //         next({path: '/curations'})
        //         return;
        //     }
        //     next()
        // }
    },
    {
        path: '/curations',
        component: Curations,
        children: [{
                path: '',
                component: CurationList,
                name: 'curations-index'
            },
            {
                path: 'create',
                component: CurationCreate,
                name: 'curations-create',
                beforeEnter: (to, from, next) => {
                    if (!user.canAddCurations()) {
                        next({ path: '/curations' })
                        return;
                    }

                    next()
                }
            },
            {
                path: 'export',
                component: CurationExportForm
            },
            {
                path: ':id',
                component: CurationShow,
                props: true,
                name: 'curations-show'
            },
            {
                path: ':id/edit',
                component: CurationEdit,
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
        ]
    },
    {
        name: 'GeneBulkLookup',
        path: '/bulk-lookup/genes',
        component: GeneBulkLookup
    },
    {
        name: 'BulkCurationLookup',
        path: '/bulk-lookup/curations',
        component: BulkLookup,
    },
    {
        name: 'BulkLookup',
        path: '/bulk-lookup',
        redirect: {name: 'BulkCurationLookup'},
    }
]

const router = new VueRouter({
    routes
})

export default router
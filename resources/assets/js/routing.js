import Vue from 'vue'
import VueRouter from 'vue-router'
import store from './store/index'

const Curations = () =>
    import ( /* webpackChunkName: "curations" */ './components/Curations/Curation')
const CurationCreate = () =>
    import ( /* webpackChunkName: "CurationCreate" */ './components/Curations/Create')
const CurationEdit = () =>
    import ( /* webpackChunkName: "CurationEdit" */ './components/Curations/Edit')
const CurationShow = () =>
    import ( /* webpackChunkName: "CurationShow" */ './components/Curations/Show')
const CurationList = () =>
    import ( /* webpackChunkName: "CurationList" */ './components/Curations/List')
const CriteriaOverview = () =>
    import ( /* webpackChunkName: "CriteriaOverview" */ './components/CriteriaOverview')
const WorkingGroups = () =>
    import ( /* webpackChunkName: "WorkingGroups" */ './components/WorkingGroups/Index')
const GroupList = () =>
    import ( /* webpackChunkName: "GroupList" */ './components/WorkingGroups/List')
const GroupShow = () =>
    import ( /* webpackChunkName: "GroupShow" */ './components/WorkingGroups/Show')
const UserDashboard = () =>
    import ( /* webpackChunkName: "UserDashboard" */ './components/UserDashboard')
const CurationExportForm = () =>
    import ( /* webpackChunkName: "CurationExportForm" */ './components/Curations/ExportForm')
const BulkLookup = () =>
    import ( /* webpackChunkName: "BulkLookup" */ './components/Curations/BulkLookup')

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
        path: '/bulk-lookup',
        component: BulkLookup,
    }
]

const router = new VueRouter({
    routes
})

export default router
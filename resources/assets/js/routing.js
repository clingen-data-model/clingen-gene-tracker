import { createRouter, createWebHashHistory } from 'vue-router'
import store from './store/index'

const Curations = () =>
    import('./components/Curations/Curation.vue')
const CurationCreate = () =>
    import('./components/Curations/Create.vue')
const CurationEdit = () =>
    import('./components/Curations/Edit.vue')
const CurationShow = () =>
    import('./components/Curations/Show.vue')
const CurationList = () =>
    import('./components/Curations/List.vue')
const CriteriaOverview = () =>
    import('./components/CriteriaOverview.vue')
const WorkingGroups = () =>
    import('./components/WorkingGroups/Index.vue')
const GroupList = () =>
    import('./components/WorkingGroups/List.vue')
const GroupShow = () =>
    import('./components/WorkingGroups/Show.vue')
const UserDashboard = () =>
    import('./components/UserDashboard.vue')
const CurationExportForm = () =>
    import('./components/Curations/ExportForm.vue')
const BulkLookup = () =>
    import('./components/Curations/BulkLookup.vue')
const GeneBulkLookup = () =>
    import('./components/GeneBulkLookup.vue')
const Administration = () =>
    import('./components/admin/Administration.vue')
const AdministrationHome = () =>
    import('./components/admin/Home.vue')
const AdminCurationTypes = () =>
    import('./components/admin/CurationTypes.vue')
const AdminRationales = () =>
    import('./components/admin/Rationales.vue')
const AdminCurationStatuses = () =>
    import('./components/admin/CurationStatuses.vue')
const AdminUploadCategories = () =>
    import('./components/admin/UploadCategories.vue')
const AdminMois = () =>
    import('./components/admin/Mois.vue')

const requireAdministrator = () => {
    const user = store.getters.getUser

    return user.canAccessAdministration() ? true : { path: '/curations' }
}

const requirePermission = permission => () => {
    const user = store.getters.getUser

    return user.hasPermission(permission) ? true : { name: 'admin-index' }
}

const routes = [{
        path: '/',
        component: UserDashboard,
        beforeEnter: () => {
            const user = store.getters.getUser

            if (!user.canAddCurations() && !user.isCurator()) {
                return { path: '/curations' }
            }

            return true
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
                beforeEnter: () => {
                    const user = store.getters.getUser

                    if (!user.canAddCurations()) {
                        return { path: '/curations' }
                    }

                    return true
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
    },
    {
        path: '/admin',
        component: Administration,
        beforeEnter: requireAdministrator,
        children: [
            {
                path: '',
                component: AdministrationHome,
                name: 'admin-index',
            },
            {
                path: 'curation-types',
                component: AdminCurationTypes,
                name: 'admin-curation-types',
                beforeEnter: requirePermission('list curation-types'),
            },
            {
                path: 'rationales',
                component: AdminRationales,
                name: 'admin-rationales',
                beforeEnter: requirePermission('list rationales'),
            },
            {
                path: 'curation-statuses',
                component: AdminCurationStatuses,
                name: 'admin-curation-statuses',
                beforeEnter: requirePermission('list curation-statuses'),
            },
            {
                path: 'upload-categories',
                component: AdminUploadCategories,
                name: 'admin-upload-categories',
            },
            {
                path: 'mois',
                component: AdminMois,
                name: 'admin-mois',
                beforeEnter: requirePermission('list mois'),
            },
        ],
    }
]

const router = createRouter({
    history: createWebHashHistory(),
    routes
})

export default router

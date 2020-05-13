import Vue from 'vue'
import VueRouter from 'vue-router'
import Curations from './components/Curations/Curation'
import CurationCreate from './components/Curations/Create'
import CurationEdit from './components/Curations/Edit'
import CurationShow from './components/Curations/Show'
import CurationList from './components/Curations/List'
import CriteriaOverview from './components/CriteriaOverview'
import WorkingGroups from './components/WorkingGroups/Index'
import GroupList from './components/WorkingGroups/List'
import GroupShow from './components/WorkingGroups/Show'
import UserDashboard from './components/UserDashboard'
import CurationExportForm from './components/Curations/ExportForm'
import BulkLookup from './components/Curations/BulkLookup'

Vue.use(VueRouter)

const routes = [
    {
        path: '',
        component: UserDashboard
    },
    {
        path: '/working-groups',
        component: WorkingGroups,
        children: [
            {
                path: '',
                component: GroupList
            },
            {
                path: ':id',
                component: GroupShow,
                props: true
            }
        ]
    },
    { 
        path: '/curations',
        component: Curations,
        children: [
            {
                path: '',
                component: CurationList,
                name: 'curations-index'
            },
            { 
                path: 'create', 
                component: CurationCreate,
                name: 'curations-create'
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
                name: 'curations-edit'
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
import Vue from 'vue'
import VueRouter from 'vue-router'
import Topics from './components/Topics/Topics'
import TopicCreate from './components/Topics/Create'
import TopicEdit from './components/Topics/Edit'
import TopicShow from './components/Topics/Show'
import TopicList from './components/Topics/List'
import CriteriaOverview from './components/CriteriaOverview'
import WorkingGroups from './components/WorkingGroups/Index'
import GroupList from './components/WorkingGroups/List'
import GroupShow from './components/WorkingGroups/Show'
import UserDashboard from './components/UserDashboard'

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
        path: '/topics',
        component: Topics,
        children: [
            {
                path: '',
                component: TopicList
            },
            { 
                path: 'create', 
                component: TopicCreate
            },
            {
                path: ':id',
                component: TopicShow,
                props: true
            },
            { 
                path: ':id/edit', 
                component: TopicEdit,
                props: true
            },
        ]
    }
]

const router = new VueRouter({
  routes
})

export default router
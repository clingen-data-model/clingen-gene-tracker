import Vue from 'vue'
import VueRouter from 'vue-router'
import Topics from './components/Topics/Topics'
import TopicCreate from './components/Topics/Create'
import TopicEdit from './components/Topics/Edit'
import TopicShow from './components/Topics/Show'
import TopicList from './components/Topics/List'
import CriteriaOverview from './components/CriteriaOverview'

Vue.use(VueRouter)

const routes = [
    { 
        path: '/topics',
        alias: '' ,
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
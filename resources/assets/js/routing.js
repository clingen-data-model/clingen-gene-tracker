import Vue from 'vue'
import VueRouter from 'vue-router'
import Topics from './components/Topics/Topics'
import NewTopic from './components/Topics/NewTopic'
import EditTopic from './components/Topics/EditTopic'
import ShowTopic from './components/Topics/ShowTopic'
import InfoFields from './components/Topics/InfoFields'
import PhenotypeSelection from './components/Topics/Phenotypes/Selection'
import List from './components/Topics/List'

Vue.use(VueRouter)

const routes = [
    { 
        path: '/topics',
        alias: '' ,
        component: Topics,
        children: [
            {
                path: '',
                component: List
            },
            { 
                path: 'create', 
                component: NewTopic
            },
            {
                path: ':id',
                component: ShowTopic,
                props: true
            },
            { 
                path: ':id/edit', 
                component: EditTopic,
                props: true
            },
        ]
    },
]

const router = new VueRouter({
  routes
})

export default router
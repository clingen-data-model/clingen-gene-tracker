import Vue from 'vue'
import VueRouter from 'vue-router'
import Topics from './components/Topics/Topics'
import NewTopic from './components/Topics/NewTopic'
import EditTopic from './components/Topics/EditTopic'
import ShowTopic from './components/Topics/ShowTopic'
import InfoFields from './components/Topics/InfoFields'
import PhenotypeSelection from './components/Topics/Phenotypes/Selection'

Vue.use(VueRouter)

const routes = [
    { 
        path: '/', 
        component: Topics
    },
    { 
        path: '/topics', 
        component: Topics
    },
    { 
        path: '/topics/create', 
        component: NewTopic
    },
    { 
        path: '/topics/:id/edit', 
        component: EditTopic,
        props: true
    },
    {
        path: '/topics/:id',
        component: ShowTopic,
        props: true
    }
]

const router = new VueRouter({
  routes
})

export default router
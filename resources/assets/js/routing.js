import Vue from 'vue'
import VueRouter from 'vue-router'
import Topics from './components/topics/Topics'
import NewTopic from './components/topics/NewTopic'
import EditTopic from './components/topics/EditTopic'

Vue.use(VueRouter)

const Foo = { template: '<div>foo</div>' }
const Bar = { template: '<div>bar</div>' }

const routes = [
    { path: '/', component: Topics},
    { path: '/topics', component: Topics},
    { path: '/topics/create', component: NewTopic},
    { 
        path: '/topics/:id/edit', 
        component: EditTopic,
        props: true
    },
]

const router = new VueRouter({
  routes
})

export default router
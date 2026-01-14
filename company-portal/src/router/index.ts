import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '../views/Dashboard.vue'

import LoginView from '../views/auth/LoginView.vue'
import RegisterView from '../views/auth/RegisterView.vue'

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: LoginView,
            meta: { layout: 'auth' }
        },
        {
            path: '/register',
            name: 'register',
            component: RegisterView,
            meta: { layout: 'auth' }
        },
        {
            path: '/',
            redirect: '/dashboard'
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: Dashboard
        },
        {
            path: '/users',
            name: 'users',
            component: () => import('../views/Users.vue')
        },
        {
            path: '/invoices',
            name: 'invoices',
            component: () => import('../views/Invoices.vue')
        },
        {
            path: '/calls',
            name: 'calls',
            component: () => import('../views/Calls.vue')
        },
        {
            path: '/call-history',
            name: 'call-history',
            component: () => import('../views/CallHistory.vue')
        },
        {
            path: '/activity-logs',
            name: 'activity-logs',
            component: () => import('../views/ActivityLogs.vue')
        },
        {
            path: '/invoices/:id/calls',
            name: 'invoice-calls',
            component: () => import('../views/InvoiceCalls.vue')
        },
        {
            path: '/agents',
            name: 'agents',
            component: () => import('../views/Agents.vue')
        },
        {
            path: '/agents/request',
            name: 'request-agent',
            component: () => import('../views/RequestAgent.vue')
        },
        {
            path: '/live-demo',
            name: 'live-demo',
            component: () => import('../views/LiveDemo.vue'),
        },
        // Marketing/Landing page or Auth will go here later
    ]
})

export default router

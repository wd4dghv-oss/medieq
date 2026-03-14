import { createRouter, createWebHistory } from 'vue-router'
import { supabase } from './supabase'

const routes = [
    {
        path: '/',
        component: () => import('./layouts/MainLayout.vue'),
        children: [
            { path: '', component: () => import('./pages/LoginPage.vue') },
            { path: 'dashboard', component: () => import('./pages/DashboardPage.vue'), meta: { requiresAuth: true } },
            { path: 'admin', component: () => import('./pages/AdminDashboardPage.vue'), meta: { requiresAuth: true, requiresAdmin: true } },
            { path: 'device/:id', component: () => import('./pages/DeviceDetailsPage.vue'), meta: { requiresAuth: true } }, // New Route
            { path: 'account', component: () => import('./pages/AccountPage.vue'), meta: { requiresAuth: true } },
            { path: 'unauthorized', component: () => import('./pages/Error403.vue') }
        ]
    },
    {
        path: '/:catchAll(.*)*',
        component: () => import('./pages/Error404.vue')
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

import { useAuthStore } from './stores/auth'

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()

    // Ensure auth is initialized
    if (authStore.loading) {
        await authStore.initialize()
    }

    const isLoggedIn = !!authStore.user

    if (to.matched.some(record => record.meta.requiresAuth)) {
        if (!isLoggedIn) {
            next({ path: '/' })
            return
        }

        if (to.matched.some(record => record.meta.requiresAdmin)) {
            if (authStore.profile?.role !== 'admin') {
                next({ path: '/dashboard' })
                return
            }
        }
    }

    if (to.path === '/' && isLoggedIn) {
        if (authStore.profile?.role === 'admin') {
            next({ path: '/admin' })
        } else {
            next({ path: '/dashboard' })
        }
        return
    }

    next()
})


export default router

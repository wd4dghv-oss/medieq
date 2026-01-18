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

router.beforeEach(async (to, from, next) => {
    const { data: { session } } = await supabase.auth.getSession()

    if (to.matched.some(record => record.meta.requiresAuth)) {
        if (!session) {
            next({ path: '/' })
            return
        }

        if (to.matched.some(record => record.meta.requiresAdmin)) {
            const { data: profile } = await supabase
                .from('profiles')
                .select('role')
                .eq('id', session.user.id)
                .single()

            if (profile?.role !== 'admin') {
                next({ path: '/dashboard' })
                return
            }
        }
    }

    if (to.path === '/' && session) {
        const { data: profile } = await supabase
            .from('profiles')
            .select('role')
            .eq('id', session.user.id)
            .single()

        if (profile?.role === 'admin') {
            next({ path: '/admin' })
        } else {
            next({ path: '/dashboard' })
        }
        return
    }

    next()
})

export default router

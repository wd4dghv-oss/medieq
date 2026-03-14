
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { supabase } from '../supabase'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const profile = ref(null)
    const loading = ref(true)

    async function fetchProfile(userId) {
        const { data, error } = await supabase
            .from('profiles')
            .select('*')
            .eq('id', userId)
            .single()

        if (!error) {
            profile.value = data
        }
    }

    async function initialize() {
        loading.value = true
        try {
            const { data: { session }, error } = await supabase.auth.getSession()
            if (error) {
                console.error('Supabase session error:', error)
            } else if (session) {
                user.value = session.user
                await fetchProfile(session.user.id)
            }
        } catch (err) {
            console.error('Failed to initialize auth session:', err)
        } finally {
            loading.value = false
        }

        supabase.auth.onAuthStateChange(async (event, session) => {
            if (session) {
                user.value = session.user
                await fetchProfile(session.user.id)
            } else {
                user.value = null
                profile.value = null
            }
        })
    }

    return { user, profile, loading, initialize, fetchProfile }
})

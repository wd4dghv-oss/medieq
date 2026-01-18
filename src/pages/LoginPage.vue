<template>
  <q-page class="flex flex-center bg-gradient">
    <div class="row full-width justify-center items-center" style="min-height: 100vh">
      
      <!-- Content Wrapper -->
      <div class="col-11 col-sm-8 col-md-6 col-lg-4">
        <q-card class="q-pa-lg glass-login no-shadow-border">
          <q-card-section class="text-center q-mb-md">
            <div class="text-h4 text-weight-bold text-primary q-mb-xs">Medieq</div>
            <div class="text-subtitle1 text-grey-7">
              {{ isLoginMode ? 'Sign in to your account' : 'Create a new account' }}
            </div>
          </q-card-section>

          <q-card-section>
            <q-form @submit="handleSubmit" class="q-gutter-md">
              <q-input
                filled
                v-model="email"
                label="Email Address"
                type="email"
                bg-color="white"
                hide-bottom-space
                :rules="[ val => val && val.length > 0 || 'Required']"
              >
                <template v-slot:prepend>
                  <q-icon name="email" class="text-primary" />
                </template>
              </q-input>

              <q-input
                filled
                v-model="password"
                label="Password"
                type="password"
                bg-color="white"
                hide-bottom-space
                :rules="[ val => val && val.length > 6 || 'Password must be at least 6 characters']"
              >
                <template v-slot:prepend>
                  <q-icon name="lock" class="text-primary" />
                </template>
              </q-input>

              <div class="q-mt-lg">
                <q-btn 
                  :label="isLoginMode ? 'Login' : 'Sign Up'" 
                  type="submit" 
                  color="primary" 
                  class="full-width q-py-sm text-weight-bold shadow-2 hover-lift" 
                  rounded
                  :loading="loading"
                />
              </div>

              <div class="text-center q-mt-sm">
                 <span class="text-grey-7">{{ isLoginMode ? 'New here?' : 'Already have an account?' }}</span>
                 <q-btn 
                   flat 
                   :label="isLoginMode ? 'Create Account' : 'Login'" 
                   color="secondary" 
                   size="sm" 
                   class="text-weight-bold" 
                   @click="toggleMode" 
                 />
              </div>

            </q-form>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { supabase } from '../supabase'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const router = useRouter()
const email = ref('')
const password = ref('')
const isLoginMode = ref(true)
const loading = ref(false)

const toggleMode = () => {
  isLoginMode.value = !isLoginMode.value
  // Optional: clear fields or keep them
}

const handleSubmit = async () => {
  loading.value = true
  try {
    if (isLoginMode.value) {
      // Login Logic
       const { data, error } = await supabase.auth.signInWithPassword({
        email: email.value,
        password: password.value
      })

      if (error) throw error

      $q.notify({ type: 'positive', message: 'Welcome back!', position: 'top' })
      const { data: profile } = await supabase.from('profiles').select('role').eq('id', data.user.id).single()
      if (profile?.role === 'admin') router.push('/admin')
      else router.push('/dashboard')

    } else {
      // Signup Logic
      const { data, error } = await supabase.auth.signUp({
        email: email.value,
        password: password.value
      })

      if (error) throw error

      $q.notify({ type: 'positive', message: 'Account created!', position: 'top' })
      
      // Auto login checks
      if (data.session) {
         $q.notify({ type: 'positive', message: 'You are now logged in.', position: 'top' })
         router.push('/dashboard')
      } else {
         $q.notify({ type: 'info', message: 'Please check your email to confirm your account.', position: 'top', timeout: 5000 })
         isLoginMode.value = true // Switch back to login
      }
    }
  } catch (error) {
    console.error(error)
     $q.notify({ type: 'negative', message: error.message || 'An error occurred', position: 'top' })
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.bg-gradient {
  background: linear-gradient(135deg, #E0F2FE 0%, #F3F4F6 100%);
}
.glass-login {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 24px !important;
}
.no-shadow-border {
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
}
.hover-lift:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
}
</style>

<template>
  <q-page class="flex flex-center bg-gradient">
    <div class="row full-width justify-center items-center" style="min-height: 100vh">
      
      <!-- Content Wrapper -->
      <div class="col-11 col-sm-8 col-md-6 col-lg-4">
        <q-card class="q-pa-lg glass-login no-shadow-border">
          <q-card-section class="text-center q-mb-md">
            <div class="text-h4 text-weight-bold text-primary q-mb-xs">Medieq</div>
            <div class="text-subtitle1 text-grey-7">
              {{ isLoginMode ? 'Sign in to your ward' : 'Create a new account' }}
            </div>
          </q-card-section>

          <q-card-section>
            <q-form @submit="handleSubmit" class="q-gutter-y-md">
              <q-input
                filled
                v-model="email"
                label="Email Address"
                type="email"
                bg-color="white"
                hide-bottom-space
                :rules="[ val => !!val || 'Email is required']"
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
                :rules="[ val => val && val.length >= 6 || 'Minimum 6 characters']"
              >
                <template v-slot:prepend>
                  <q-icon name="lock" class="text-primary" />
                </template>
              </q-input>

              <!-- Password Confirmation (Signup only) -->
              <q-input
                v-if="!isLoginMode"
                filled
                v-model="confirmPassword"
                label="Confirm Password"
                type="password"
                bg-color="white"
                hide-bottom-space
                :rules="[ 
                  val => !!val || 'Required',
                  val => val === password || 'Passwords do not match'
                ]"
              >
                <template v-slot:prepend>
                  <q-icon name="lock_reset" class="text-primary" />
                </template>
              </q-input>

              <!-- Ward Input (Signup: Text Input) -->
              <q-input
                v-if="!isLoginMode"
                filled
                v-model="ward"
                label="Type Ward Name"
                placeholder="e.g. Ward 15 or 'admin' for management"
                bg-color="white"
                hide-bottom-space
                :rules="[ val => !!val || 'Ward name is required']"
              >
                <template v-slot:prepend>
                  <q-icon name="meeting_room" class="text-primary" />
                </template>
                <template v-slot:hint>
                   Type 'admin' to create an administrator account.
                </template>
              </q-input>

              <!-- Ward Selection (Login: Dropdown Only) -->
              <q-select
                v-else
                filled
                v-model="selectedWard"
                label="Select Your Ward"
                placeholder="Choose registered ward"
                bg-color="white"
                hide-bottom-space
                :options="wardOptions"
                :loading="loadingWards"
                @popup-show="fetchRegisteredWards"
                class="ward-select"
                :rules="[ val => !!val || 'Please select a ward']"
              >
                <template v-slot:prepend>
                  <q-icon name="meeting_room" class="text-primary" />
                </template>
                <template v-slot:no-option>
                  <q-item>
                    <q-item-section class="text-grey italic">
                      No wards found. Please register your ward first.
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>

              <div class="q-mt-md">
                <q-btn 
                  :label="isLoginMode ? 'Login' : 'Register Account'" 
                  type="submit" 
                  color="primary" 
                  class="full-width q-py-sm text-weight-bold shadow-2 hover-lift" 
                  rounded
                  :loading="loading"
                />
              </div>

              <div class="text-center q-mt-sm">
                 <span class="text-grey-7">{{ isLoginMode ? 'New यहाँ?' : 'ඔබට ගිණුමක් තිබේද?' }}</span>
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { supabase } from '../supabase'
import { useQuasar } from 'quasar'
import { useAuthStore } from '../stores/auth'

const $q = useQuasar()
const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const ward = ref('') // Used in signup
const selectedWard = ref(null) // Used in login dropdown
const isLoginMode = ref(true)
const loading = ref(false)
const loadingWards = ref(false)
const wardOptions = ref([])

const fetchRegisteredWards = async () => {
  loadingWards.value = true
  const { data, error } = await supabase.rpc('get_unique_wards')
  if (!error && data) {
    wardOptions.value = data.map(item => item.ward_name)
  }
  loadingWards.value = false
}

onMounted(() => {
  fetchRegisteredWards()
})

const toggleMode = () => {
  isLoginMode.value = !isLoginMode.value
  fetchRegisteredWards()
}

const handleSubmit = async () => {
  loading.value = true
  try {
    if (isLoginMode.value) {
      // --- LOGIN LOGIC ---
       const { data, error } = await supabase.auth.signInWithPassword({
        email: email.value,
        password: password.value
      })

      if (error) throw error

      // Fetch profile to verify ward selection
      let { data: profile } = await supabase
        .from('profiles')
        .select('*')
        .eq('id', data.user.id)
        .maybeSingle()
      
      if (!profile) {
         // This shouldn't happen with proper signup, but let's handle it
         throw new Error('User profile not found. Please contact admin.')
      }

      // Verify selected ward matches stored ward
      const typedWard = selectedWard.value?.toLowerCase()
      const storedWard = profile.ward?.toLowerCase() || ''

      if (storedWard !== typedWard) {
         await supabase.auth.signOut()
         throw new Error(`Access Denied: Your account is registered for "${profile.ward}", not "${selectedWard.value}".`)
      }

      $q.notify({ type: 'positive', message: 'Signed in successfully!', position: 'top' })
      await authStore.fetchProfile(data.user.id)
      
      // Route based on role
      if (authStore.profile?.role === 'admin') router.push('/admin')
      else router.push('/dashboard')

    } else {
      // --- SIGNUP LOGIC ---
      if (password.value !== confirmPassword.value) throw new Error('Passwords do not match')

      const typedWard = ward.value.trim()
      const isAdmin = typedWard.toLowerCase() === 'admin'

      const { data, error } = await supabase.auth.signUp({
        email: email.value,
        password: password.value
      })

      if (error) throw error

      if (data.user) {
        // Create profile
        const { error: profileError } = await supabase
          .from('profiles')
          .insert([
            { 
              id: data.user.id, 
              email: email.value.toLowerCase(), 
              ward: isAdmin ? 'Admin' : typedWard, 
              role: isAdmin ? 'admin' : 'user' 
            }
          ])
        if (profileError) console.error(profileError)
      }

      $q.notify({ type: 'positive', message: 'Account registered! You can now log in.', position: 'top' })
      isLoginMode.value = true
      fetchRegisteredWards()
    }
  } catch (err) {
     $q.notify({ type: 'negative', message: err.message || 'Authentication failed', position: 'top' })
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
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 28px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.05);
}
.hover-lift {
  transition: all 0.25s ease;
}
.hover-lift:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(25, 118, 210, 0.2) !important;
}
</style>

<template>
  <q-page :class="$q.dark.isActive ? 'q-pa-md q-pa-lg-xl bg-dark text-white' : 'q-pa-md q-pa-lg-xl bg-grey-1'">
    <div class="row q-col-gutter-lg justify-center">
      <!-- Left Sidebar (Tabs) -->
      <div class="col-12 col-md-3">
        <q-card :class="$q.dark.isActive ? 'bg-dark' : 'bg-white'" class="rounded-borders navbar-card">
          <q-card-section class="text-center q-pa-lg">
             <q-avatar size="100px" color="primary" text-color="white" class="shadow-3">
                {{ user?.email?.charAt(0).toUpperCase() }}
             </q-avatar>
             <div class="text-h6 q-mt-md">{{ formData.firstName || user?.email?.split('@')[0] }}</div>
             <div class="text-caption text-grey">{{ role === 'admin' ? 'Administrator' : 'Medical Staff' }}</div>
          </q-card-section>
          
          <q-separator />

          <q-tabs vertical v-model="activeTab" active-color="primary" indicator-color="primary" class="text-grey-7">
             <q-tab name="general" icon="person" label="General" align="left" class="q-py-md" />
             <q-tab name="security" icon="security" label="Security" align="left" class="q-py-md" />
             <q-tab name="preferences" icon="tune" label="Preferences" align="left" class="q-py-md" />
          </q-tabs>
        </q-card>
      </div>

      <!-- Right Content -->
      <div class="col-12 col-md-9">
        <q-card :class="$q.dark.isActive ? 'bg-dark' : 'bg-white'" class="rounded-borders full-height navbar-card">
           <q-tab-panels v-model="activeTab" animated vertical transition-prev="fade" transition-next="fade" class="bg-transparent">
              
              <!-- General Tab -->
              <q-tab-panel name="general" class="q-pa-lg">
                 <div class="text-h6 q-mb-md">Profile Settings</div>
                 <div class="text-caption text-grey q-mb-lg">Manage your personal information</div>
                 
                 <div class="row q-col-gutter-md q-mb-md">
                    <div class="col-12 col-sm-6">
                       <q-input filled v-model="formData.firstName" label="First Name" />
                    </div>
                    <div class="col-12 col-sm-6">
                       <q-input filled v-model="formData.lastName" label="Last Name" />
                    </div>
                 </div>

                 <q-input filled v-model="userEmail" label="Email Address" readonly hint="Contact admin to change email" class="q-mb-md" />
                 <q-input filled v-model="formData.phone" label="Phone Number" class="q-mb-lg" />
                 
                 <div class="row justify-end">
                    <q-btn label="Save Changes" color="primary" unelevated @click="updateProfile" :loading="loading" />
                 </div>
              </q-tab-panel>

              <!-- Security Tab -->
              <q-tab-panel name="security" class="q-pa-lg">
                 <div class="text-h6 q-mb-md">Security & Login</div>
                 
                 <div class="q-banner bg-blue-1 text-primary rounded-borders q-mb-lg q-pa-md">
                    <div class="row items-center">
                       <q-icon name="info" size="sm" class="q-mr-sm" />
                       <div>To update your password, please verify your existing password first.</div>
                    </div>
                 </div>

                 <q-form @submit="updatePassword" class="q-gutter-md" style="max-width: 400px">
                    <q-input 
                       filled 
                       v-model="securityForm.oldPassword" 
                       type="password" 
                       label="Current Password" 
                       :rules="[val => !!val || 'Required']"
                    />
                    
                    <q-separator class="q-my-md" />

                    <q-input 
                       filled 
                       v-model="securityForm.newPassword" 
                       type="password" 
                       label="New Password" 
                       hint="Minimum 6 characters"
                       :rules="[val => val.length >= 6 || 'At least 6 characters required']"
                    />
                    <q-input 
                       filled 
                       v-model="securityForm.confirmPassword" 
                       type="password" 
                       label="Confirm New Password" 
                       :rules="[val => val === securityForm.newPassword || 'Passwords do not match']"
                    />

                    <div class="row justify-start q-mt-lg">
                       <q-btn label="Update Password" type="submit" color="primary" unelevated :loading="loading" />
                    </div>
                 </q-form>
              </q-tab-panel>

              <!-- Preferences Tab -->
              <q-tab-panel name="preferences" class="q-pa-lg">
                 <div class="text-h6 q-mb-md">App Preferences</div>
                 
                 <q-list separator>
                    <q-item tag="label" v-ripple class="q-px-none">
                       <q-item-section>
                          <q-item-label>Language</q-item-label>
                          <q-item-label caption>Select your preferred interface language</q-item-label>
                       </q-item-section>
                       <q-item-section side>
                          <q-select 
                            v-model="language" 
                            :options="['English', 'Sinhala']" 
                            dense 
                            borderless 
                            options-dense
                            style="min-width: 100px"
                          />
                       </q-item-section>
                    </q-item>

                    <q-item tag="label" v-ripple class="q-px-none">
                       <q-item-section>
                          <q-item-label>Dark Mode</q-item-label>
                          <q-item-label caption>Switch to a dark theme interface</q-item-label>
                       </q-item-section>
                       <q-item-section side>
                          <q-toggle v-model="isDark" @update:model-value="toggleDark" color="primary" />
                       </q-item-section>
                    </q-item>

                    <q-item tag="label" v-ripple class="q-px-none">
                       <q-item-section>
                          <q-item-label>Email Notifications</q-item-label>
                          <q-item-label caption>Receive updates about device alerts and maintenance</q-item-label>
                       </q-item-section>
                       <q-item-section side>
                          <q-toggle v-model="notifications" color="primary" />
                       </q-item-section>
                    </q-item>

                     <q-item tag="label" v-ripple class="q-px-none">
                       <q-item-section>
                          <q-item-label>Auto-Logout</q-item-label>
                          <q-item-label caption>Automatically log out after 30 minutes of inactivity</q-item-label>
                       </q-item-section>
                       <q-item-section side>
                          <q-toggle v-model="autoLogout" color="primary" />
                       </q-item-section>
                    </q-item>
                 </q-list>
              </q-tab-panel>

           </q-tab-panels>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { supabase } from '../supabase'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const user = ref(null)
const role = ref('')
const loading = ref(false)
const activeTab = ref('general')

// General Form
const userEmail = ref('')
const formData = ref({
   firstName: '',
   lastName: '',
   phone: ''
})

// Security Form
const securityForm = ref({
   oldPassword: '',
   newPassword: '',
   confirmPassword: ''
})

// Preferences
const isDark = ref(false)
const language = ref('English')
const notifications = ref(true)
const autoLogout = ref(false)

onMounted(async () => {
  const { data: { session } } = await supabase.auth.getSession()
  if (session) {
     user.value = session.user
     userEmail.value = session.user.email
     
     // Fetch role
     const { data } = await supabase.from('profiles').select('role').eq('id', session.user.id).single()
     role.value = data?.role || 'user'

     // Load metadata if available
     const meta = session.user.user_metadata || {}
     formData.value.firstName = meta.first_name || ''
     formData.value.lastName = meta.last_name || ''
     formData.value.phone = meta.phone || ''
  }
  
  // Apply saved dark mode state
  const savedDarkMode = localStorage.getItem('darkMode') === 'true'
  $q.dark.set(savedDarkMode)
  isDark.value = $q.dark.isActive
})

const updateProfile = async () => {
  loading.value = true
  const { error } = await supabase.auth.updateUser({
     data: {
        first_name: formData.value.firstName,
        last_name: formData.value.lastName,
        phone: formData.value.phone
     }
  })
  loading.value = false

  if (error) {
     $q.notify({ type: 'negative', message: 'Update failed: ' + error.message })
  } else {
     $q.notify({ type: 'positive', message: 'Profile updated successfully' })
     // Reload user
     const { data: { user: updatedUser } } = await supabase.auth.getUser()
     user.value = updatedUser
  }
}

const updatePassword = async () => {
  loading.value = true
  
  // 1. Verify match
  if (securityForm.value.newPassword !== securityForm.value.confirmPassword) {
     $q.notify({ type: 'negative', message: 'Passwords do not match' })
     loading.value = false
     return
  }

  // 2. Verify Old Password by trying to sign in
  const { error: signInError } = await supabase.auth.signInWithPassword({
     email: user.value.email,
     password: securityForm.value.oldPassword
  })

  if (signInError) {
     $q.notify({ type: 'negative', message: 'Incorrect Current Password' })
     loading.value = false
     return
  }

  // 3. Update Password
  const { error } = await supabase.auth.updateUser({
    password: securityForm.value.newPassword
  })

  loading.value = false

  if (error) {
     $q.notify({ type: 'negative', message: 'Error: ' + error.message })
  } else {
     $q.notify({ type: 'positive', message: 'Password updated successfully' })
     securityForm.value = { oldPassword: '', newPassword: '', confirmPassword: '' }
  }
}

const toggleDark = (val) => {
   $q.dark.set(val)
}
</script>

<style scoped>
.rounded-borders {
  border-radius: 12px;
}
</style>

<template>
  <q-layout view="lHh Lpr lFf" :class="$q.dark.isActive ? 'bg-dark text-white' : 'bg-grey-1'">
    <q-header :class="$q.dark.isActive ? 'bg-dark text-white shadow-1' : 'bg-white text-dark shadow-1'">
      <q-toolbar class="q-py-md">
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          color="primary"
          @click="toggleLeftDrawer"
        />

        <q-toolbar-title class="text-weight-bold text-primary flex items-center">
          <q-icon name="health_and_safety" size="md" class="q-mr-sm text-secondary" />
          <span style="letter-spacing: -0.5px">Medi</span><span class="text-secondary" style="letter-spacing: -0.5px">EQ</span>
        </q-toolbar-title>

        <div v-if="user" class="row items-center">
           <!-- Notifications Tooltip & Menu -->
           <q-btn flat round dense icon="notifications" color="grey-7" class="q-mr-sm">
             <q-badge v-if="notifications.length > 0" color="red" floating rounded>{{ notifications.length }}</q-badge>
             <q-menu class="rounded-borders" style="width: 300px">
                <q-list>
                   <q-item-label header>Device Alerts</q-item-label>
                   <q-separator v-if="notifications.length > 0" />
                   
                   <div v-if="notifications.length === 0" class="q-pa-md text-center text-grey">
                      No critical alerts
                   </div>
                   
                   <q-item v-for="notif in notifications" :key="notif.id + notif.type" clickable v-close-popup @click="openNotification(notif)">
                      <q-item-section avatar>
                         <q-avatar :icon="notif.type === 'charge' ? 'battery_alert' : 'engineering'" :color="notif.type === 'charge' ? 'red-1' : 'orange-1'" :text-color="notif.type === 'charge' ? 'red' : 'orange-7'" />
                      </q-item-section>
                      <q-item-section>
                         <q-item-label class="text-weight-bold">{{ notif.name }}</q-item-label>
                         <q-item-label caption lines="2">
                            <span v-if="notif.type === 'charge'">
                               Needs charging. (Last: {{ notif.lastDate ? new Date(notif.lastDate).toLocaleDateString() : 'Never' }})
                            </span>
                            <span v-else>
                               BME Status: <b>{{ notif.status }}</b>. Device ready to be collected.
                            </span>
                         </q-item-label>
                      </q-item-section>
                   </q-item>
                </q-list>
             </q-menu>
           </q-btn>

           <div class="column q-mr-md text-right gt-xs">
              <div class="text-subtitle2 text-weight-bold no-margin line-height-tight">{{ user.email?.split('@')[0] }}</div>
              <div class="text-caption text-grey-6 no-margin">{{ isAdmin ? 'Administrator' : 'Medical Staff' }}</div>
           </div>

           <!-- User Profile Menu -->
           <q-avatar color="primary" text-color="white" font-size="14px" class="cursor-pointer shadow-2">
              {{ user.email?.charAt(0).toUpperCase() }}
              <q-menu class="rounded-borders" style="min-width: 200px">
                 <div class="row no-wrap q-pa-md items-center shadow-1">
                    <q-avatar size="48px" color="primary" text-color="white" class="q-mr-sm">
                       {{ user.email?.charAt(0).toUpperCase() }}
                    </q-avatar>
                    <div>
                       <div class="text-subtitle2 text-weight-bold">{{ user.email?.split('@')[0] }}</div>
                       <div class="text-caption text-grey">{{ isAdmin ? 'Admin' : 'Staff' }}</div>
                    </div>
                 </div>
                 
                 <q-list padding>
                    <q-item clickable v-ripple to="/account">
                       <q-item-section avatar>
                          <q-icon name="manage_accounts" color="primary" />
                       </q-item-section>
                       <q-item-section>Account Settings</q-item-section>
                    </q-item>
                    
                    <q-separator spaced />
                    
                    <q-item clickable v-ripple @click="handleLogout" class="text-red">
                       <q-item-section avatar>
                          <q-icon name="logout" color="red" />
                       </q-item-section>
                       <q-item-section>Sign Out</q-item-section>
                    </q-item>
                 </q-list>
              </q-menu>
           </q-avatar>
        </div>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      :class="$q.dark.isActive ? 'bg-dark sidebar-shadow' : 'bg-white sidebar-shadow'"
      :width="280"
    >
      <div class="column full-height">
        <!-- Sidebar Header -->
        <div class="q-pa-lg">
           <div class="text-h6 text-weight-bold text-primary">Portal Navigation</div>
           <div class="text-caption text-grey-7">Manage your medical assets</div>
        </div>

        <q-list padding class="text-grey-8 q-px-md">
          <div class="text-caption text-grey-6 q-mb-sm q-pl-sm text-uppercase text-weight-bold" style="font-size: 0.75rem">Main</div>
          
          <q-item 
            clickable 
            v-ripple 
            @click="goToDashboard"
            v-if="user" 
            :active="route.path === '/dashboard' && !route.query.category"
            active-class="active-menu-item"
            class="q-mb-sm menu-item"
          >
            <q-item-section avatar>
              <q-icon name="grid_view" />
            </q-item-section>
            <q-item-section class="text-weight-medium">
              Dashboard
            </q-item-section>
          </q-item>

          <q-item 
            clickable 
            v-ripple 
            to="/admin" 
            v-if="isAdmin" 
            active-class="active-menu-item"
            class="q-mb-sm menu-item"
          >
            <q-item-section avatar>
              <q-icon name="settings_suggest" />
            </q-item-section>
            <q-item-section class="text-weight-medium">
              Admin Control
            </q-item-section>
          </q-item>

          <q-expansion-item
             icon="category"
             label="Categories"
             class="q-mb-sm menu-item overflow-hidden"
             header-class="text-weight-medium"
          >
             <q-list class="q-pl-md">
                <q-item 
                  v-for="cat in categories" 
                  :key="cat" 
                  clickable 
                  dense 
                  v-ripple 
                  class="text-grey-8 rounded-borders q-mb-xs"
                  @click="navigateToCategory(cat)"
                >
                   <q-item-section>
                      <q-item-label>{{ cat }}</q-item-label>
                   </q-item-section>
                </q-item>
             </q-list>
          </q-expansion-item>
        </q-list>

        <!-- Sidebar Footer -->
        <q-space />

        <div class="q-pa-md text-center border-top">
           <div class="text-caption text-grey-6 text-weight-medium">v1.2.5</div>
           <div class="text-caption text-grey-5">© 2026 MediEQ Systems. All rights reserved.</div>
        </div>
      </div>
    </q-drawer>

    <q-page-container>
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { supabase } from '../supabase'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const router = useRouter()
const route = useRoute()
const leftDrawerOpen = ref(false)
const user = ref(null)
const profile = ref(null)
const notifications = ref([])

// Refresh alerts on navigation
watch(() => route.path, () => {
   if (user.value) checkAllAlerts()
})

const isAdmin = computed(() => {
  return profile.value?.role === 'admin'
})

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

const categories = [
  'Cardiac Monitor', 
  'Infusion Pump', 
  'Syringe Pump', 
  'CPAP', 
  'Ventilator', 
  'High Flow Machine', 
  'Centrifuge', 
  'SpO2 Sensor', 
  'Concentrator', 
  'Other'
]

function navigateToCategory (cat) {
  router.push({ path: '/dashboard', query: { category: cat } })
}

const goToDashboard = () => {
  router.push('/dashboard')
}

const openNotification = (notif) => {
  // Navigate to device
  router.push(`/device/${notif.id}`)
  // We no longer remove it locally, it will persist until the database is updated
}

const checkAllAlerts = async () => {
   try {
      const { data: devices } = await supabase.from('devices').select('id, device_name, device_id')
      if (!devices) return

      // Charging Alerts
      const { data: allCharges } = await supabase
         .from('charging_charts')
         .select('device_id, created_at')
         .order('created_at', { ascending: false })

      // BME Alerts - Get all where receive_date is null
      const { data: bmeLogs } = await supabase
         .from('bme_charts')
         .select('device_id, status, send_date, receive_date')
         .is('receive_date', null)
         .order('send_date', { ascending: false })

      const alerts = []
      const weekAgo = new Date()
      weekAgo.setDate(weekAgo.getDate() - 7)

      devices.forEach(dev => {
         // 1. Charge Logic: Notify if last charge > 7 days or never charged
         const lastChargeEntry = allCharges?.find(c => c.device_id === dev.device_id)
         const lastChargeDate = lastChargeEntry ? new Date(lastChargeEntry.created_at) : null
         
         if (!lastChargeDate || lastChargeDate < weekAgo) {
            alerts.push({
               id: dev.id,
               type: 'charge',
               name: dev.device_name,
               lastDate: lastChargeDate
            })
         }

         // 2. BME Logic: Notify if there is ANY BME record with no receive_date
         const pendingBme = bmeLogs?.find(b => b.device_id === dev.device_id)
         if (pendingBme) {
            alerts.push({
               id: dev.id,
               type: 'bme',
               name: dev.device_name,
               status: pendingBme.status,
               sendDate: pendingBme.send_date
            })
         }
      })
      
      notifications.value = alerts
   } catch (err) {
      console.error('Alert Check Error:', err)
   }
}

const handleLogout = async () => {
  await supabase.auth.signOut()
  router.push('/')
  $q.notify({
    type: 'positive',
    message: 'Logged out successfully',
    position: 'top'
  })
  user.value = null
  profile.value = null
}

let alertInterval = null

onMounted(async () => {
  // Apply saved dark mode state
  const savedDarkMode = localStorage.getItem('darkMode') === 'true'
  $q.dark.set(savedDarkMode)

  const { data: { session } } = await supabase.auth.getSession()
  if (session) {
    user.value = session.user
    const { data } = await supabase
      .from('profiles')
      .select('*')
      .eq('id', session.user.id)
      .single()
    profile.value = data
    
    // Check Alerts immediately
    checkAllAlerts()
  }

  // Auto-refresh alerts every 30 seconds
  alertInterval = setInterval(checkAllAlerts, 30000)

  supabase.auth.onAuthStateChange((_event, session) => {
    user.value = session?.user || null
    if (!session) {
       profile.value = null
       notifications.value = []
    } else {
       checkAllAlerts()
    }
  })
})

onUnmounted(() => {
   if (alertInterval) clearInterval(alertInterval)
})
</script>

<script>
// Separate script block for global style logic if needed, 
// but we'll stick to script setup for logic.
</script>

<style>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.menu-item { border-radius: 12px; transition: all 0.3s ease; }
.menu-item:hover { background-color: #f1f5f9; }

.active-menu-item {
  background: linear-gradient(135deg, #1976D2 0%, #0D47A1 100%);
  color: white !important;
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
}

.active-menu-item .q-icon { color: white !important; }
.bg-gradient-primary { background: linear-gradient(135deg, #06B6D4 0%, #3B82F6 100%); }
.sidebar-shadow { box-shadow: 4px 0 24px rgba(0,0,0,0.05) !important; border-right: none; }
.line-height-tight { line-height: 1.2; }
.opacity-80 { opacity: 0.8; }

.body--dark { background: #121212; color: #fff; }
.body--dark .q-drawer { background: #1d1d1d !important; }
.body--dark .bg-white { background: #1d1d1d !important; color: #fff !important; }
.body--dark .text-dark { color: #fff !important; }
</style>

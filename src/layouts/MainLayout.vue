<template>
  <q-layout view="lHh Lpr lFf" class="bg-grey-1">
    <q-header class="bg-white text-dark shadow-1">
      <q-toolbar class="q-py-sm">
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          color="primary"
          @click="toggleLeftDrawer"
        />

        <q-toolbar-title class="text-weight-bold text-primary">
          <q-icon name="medical_services" class="q-mr-sm" />
          Medieq
        </q-toolbar-title>

        <div v-if="user" class="row items-center">
           <div class="q-mr-md text-subtitle2 text-grey-8 gt-xs">{{ user.email }}</div>
           <q-btn flat round dense icon="logout" color="grey-7" @click="handleLogout">
             <q-tooltip>Logout</q-tooltip>
           </q-btn>
        </div>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      class="bg-white"
      :width="260"
      bordered
    >
      <div class="q-pa-md q-mb-md">
        <div class="text-h6 text-primary text-weight-bold">Menu</div>
        <div class="text-caption text-grey">Navigation</div>
      </div>

      <q-list padding class="text-grey-8">
        <q-item 
          clickable 
          v-ripple 
          to="/dashboard" 
          v-if="user" 
          active-class="text-primary bg-blue-1"
          class="q-mb-sm rounded-borders"
        >
          <q-item-section avatar>
            <q-icon name="dashboard" />
          </q-item-section>
          <q-item-section class="text-weight-medium">
            Overview
          </q-item-section>
        </q-item>

        <q-item 
          clickable 
          v-ripple 
          to="/admin" 
          v-if="isAdmin" 
          active-class="text-primary bg-blue-1"
          class="q-mb-sm rounded-borders"
        >
          <q-item-section avatar>
            <q-icon name="admin_panel_settings" />
          </q-item-section>
          <q-item-section class="text-weight-medium">
            Admin Panel
          </q-item-section>
        </q-item>
      </q-list>
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
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { supabase } from '../supabase'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const router = useRouter()
const leftDrawerOpen = ref(false)
const user = ref(null)
const profile = ref(null)

const isAdmin = computed(() => {
  return profile.value?.role === 'admin'
})

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
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

onMounted(async () => {
  const { data: { session } } = await supabase.auth.getSession()
  if (session) {
    user.value = session.user
    const { data } = await supabase
      .from('profiles')
      .select('*')
      .eq('id', session.user.id)
      .single()
    profile.value = data
  }

  supabase.auth.onAuthStateChange((_event, session) => {
    user.value = session?.user || null
    if (!session) {
       profile.value = null
    }
  })
})
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.rounded-borders {
  border-radius: 8px;
  margin-left: 8px;
  margin-right: 8px;
}
</style>

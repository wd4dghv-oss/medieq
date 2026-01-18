<template>
  <q-page class="q-pa-md q-pa-lg-xl">
    <!-- Header Section -->
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4 text-weight-bold text-dark">Dashboard</div>
        <div class="text-subtitle1 text-grey-7">Medical Equipment Overview</div>
      </div>
      <div>
        <q-btn 
          color="primary" 
          icon="add" 
          label="Add Equipment" 
          no-caps 
          unelevated 
          class="q-mr-sm"
          @click="openAddDialog"
        />
        <q-btn icon="refresh" flat round color="grey-7" @click="fetchData" />
      </div>
    </div>
    
    <div v-if="loading" class="row justify-center q-pa-lg">
       <q-spinner-dots color="primary" size="3em" />
    </div>

    <!-- View Mode: Categories -->
    <div v-else-if="viewMode === 'categories'">
       <div v-if="Object.keys(groupedDevices).length === 0" class="text-center text-grey q-pa-xl">
          <q-icon name="medical_services" size="4em" class="q-mb-md" />
          <div class="text-h6">No equipment found.</div>
          <div>Add your first device to get started!</div>
       </div>

       <div class="row q-col-gutter-md">
          <div 
             v-for="(group, name) in groupedDevices" 
             :key="name" 
             class="col-12 col-sm-6 col-md-4 col-lg-3"
          >
             <q-card class="category-card cursor-pointer" @click="selectCategory(name)">
                <q-card-section class="row items-center no-wrap">
                   <q-avatar 
                     :color="getCategoryColor(name)" 
                     text-color="white" 
                     :icon="getCategoryIcon(name)" 
                     size="lg"
                     class="shadow-2"
                   />
                   <div class="q-ml-md">
                      <div class="text-h6 text-weight-bold">{{ name }}</div>
                      <div class="text-caption text-grey-7">{{ group.length }} Device{{ group.length !== 1 ? 's' : '' }}</div>
                   </div>
                   <q-space />
                   <q-icon name="navigate_next" color="grey-5" size="md" />
                </q-card-section>
             </q-card>
          </div>
       </div>
    </div>

    <!-- View Mode: Device List -->
    <div v-else-if="viewMode === 'list'">
       <div class="row items-center q-mb-md">
          <q-btn flat round icon="arrow_back" color="grey-8" @click="viewMode = 'categories'" class="q-mr-sm" />
          <div class="text-h5 text-weight-bold text-primary">{{ selectedCategory }}</div>
          <q-badge color="primary" align="top" class="q-ml-sm">{{ selectedDevices.length }}</q-badge>
       </div>

       <q-card class="bg-white no-shadow-border">
          <q-list separator>
             <q-item v-for="device in selectedDevices" :key="device.id" class="q-py-md hover-bg" clickable>
                <q-item-section avatar @click="router.push(`/device/${device.id}`)">
                  <q-avatar color="blue-1" text-color="primary" :icon="getCategoryIcon(selectedCategory)" />
                </q-item-section>
                
                <q-item-section @click="router.push(`/device/${device.id}`)">
                  <q-item-label class="text-weight-bold text-subtitle1">{{ device.device_name }}</q-item-label>
                  <q-item-label caption class="row items-center">
                     <q-icon name="tag" size="xs" class="q-mr-xs" /> {{ device.device_id }}
                     <span class="q-mx-sm">•</span>
                     <span>Model: {{ device.model_no || 'N/A' }}</span>
                  </q-item-label>
                </q-item-section>

                <q-item-section side>
                   <div class="row items-center">
                      <q-btn flat round icon="qr_code" color="primary" @click.stop="openQR(device)">
                          <q-tooltip>QR Code</q-tooltip>
                      </q-btn>
                      <q-btn flat round icon="edit" color="grey-7" @click.stop="openEditDialog(device)">
                          <q-tooltip>Edit</q-tooltip>
                      </q-btn>
                      <q-btn flat round icon="delete" color="negative" @click.stop="confirmDelete(device)">
                          <q-tooltip>Delete</q-tooltip>
                      </q-btn>
                      <q-icon name="chevron_right" color="grey-4" class="q-ml-sm" />
                   </div>
                </q-item-section>
             </q-item>
          </q-list>
       </q-card>
    </div>

    <!-- Add/Edit Device Dialog -->
    <q-dialog v-model="showDialog" persistent>
      <q-card style="min-width: 400px; border-radius: 12px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6 text-weight-bold">{{ isEditMode ? 'Edit Equipment' : 'Add Equipment' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-md">
          <q-form @submit="saveDevice" class="q-gutter-md">
            <q-input filled v-model="formDevice.device_name" label="Device Name" hint="e.g. Bed 5 Monitor" :rules="[val => !!val || 'Required']" />
            
            <q-select 
              filled 
              v-model="formDevice.device_group" 
              :options="categoryOptions" 
              label="Category" 
              :rules="[val => !!val || 'Required']"
            />

            <q-input filled v-model="formDevice.device_id" label="Asset ID / Tag" :rules="[val => !!val || 'Required']" />
            
            <div class="row q-col-gutter-sm">
               <div class="col-6"><q-input filled v-model="formDevice.model_no" label="Model No" /></div>
               <div class="col-6"><q-input filled v-model="formDevice.serial_no" label="Serial No" /></div>
            </div>
            
            <div class="row justify-end q-mt-lg">
               <q-btn flat label="Cancel" color="grey-7" v-close-popup class="q-mr-sm" no-caps />
               <q-btn :label="isEditMode ? 'Update' : 'Save'" type="submit" color="primary" unelevated no-caps />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { supabase } from '../supabase'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const router = useRouter()
const devices = ref([])
const loading = ref(true)

// Navigation State
const viewMode = ref('categories') // 'categories' or 'list'
const selectedCategory = ref('')

// Dialog State
const showDialog = ref(false)
const isEditMode = ref(false)
const categoryOptions = ['Cardiac Monitor', 'Infusion Pump', 'Syringe Pump', 'Ventilator', 'Other']
const formDevice = ref({
  id: null,
  device_name: '',
  device_id: '',
  device_group: '', 
  model_no: '',
  serial_no: ''
})

// --- Computed ---

const groupedDevices = computed(() => {
  const groups = {}
  devices.value.forEach(d => {
    const group = d.device_group || 'Uncategorized'
    if (!groups[group]) groups[group] = []
    groups[group].push(d)
  })
  return groups
})

const selectedDevices = computed(() => {
  return groupedDevices.value[selectedCategory.value] || []
})

// --- Helpers ---

const getCategoryIcon = (category) => {
  switch(category) {
    case 'Cardiac Monitor': return 'monitor_heart'
    case 'Infusion Pump': return 'vaccines'
    case 'Syringe Pump': return 'colorize'
    case 'Ventilator': return 'air'
    default: return 'medical_services'
  }
}

const getCategoryColor = (category) => {
   switch(category) {
    case 'Cardiac Monitor': return 'red-5'
    case 'Infusion Pump': return 'blue-5'
    case 'Syringe Pump': return 'teal-5'
    case 'Ventilator': return 'cyan-6'
    default: return 'primary'
  }
}

// --- Actions ---

const fetchData = async () => {
  loading.value = true
  try {
    const { data, error } = await supabase.from('devices').select('*').order('created_at', { ascending: false })
    if (error) throw error
    devices.value = data
  } catch (error) {
    console.error('Error:', error.message)
    $q.notify({ type: 'negative', message: 'Error loading devices' })
  } finally {
    loading.value = false
  }
}

const selectCategory = (name) => {
  selectedCategory.value = name
  viewMode.value = 'list'
}

// Add/Edit Dialog
const openAddDialog = () => {
  isEditMode.value = false
  formDevice.value = { device_name: '', device_id: '', device_group: '', model_no: '', serial_no: '' }
  showDialog.value = true
}

const openEditDialog = (device) => {
  isEditMode.value = true
  // Clone object to avoid direct mutation
  formDevice.value = { ...device }
  showDialog.value = true
}

const saveDevice = async () => {
  try {
    if (isEditMode.value) {
      // Update
      const { id, ...updates } = formDevice.value
      const { error } = await supabase.from('devices').update(updates).eq('id', id)
      if (error) throw error
      $q.notify({ type: 'positive', message: 'Device updated' })
    } else {
      // Insert
      const { id, ...newItem } = formDevice.value
      const { error } = await supabase.from('devices').insert([newItem])
      if (error) throw error
      $q.notify({ type: 'positive', message: 'Device created' })
    }
    
    showDialog.value = false
    fetchData()
  } catch (err) {
    $q.notify({ type: 'negative', message: err.message })
  }
}

const confirmDelete = (device) => {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Delete ${device.device_name}?`,
    cancel: true,
    persistent: true,
    ok: { label: 'Delete', color: 'negative', flat: true }
  }).onOk(async () => {
     const { error } = await supabase.from('devices').delete().eq('id', device.id)
     if (error) {
       $q.notify({ type: 'negative', message: error.message })
     } else {
       $q.notify({ type: 'positive', message: 'Deleted successfully' })
       fetchData()
       // If empty, go back
       if (selectedDevices.value.length <= 1) { // 1 because we haven't refetched yet fully or strictly reactive
          // simple check after fetch
       }
     }
  })
}

// Reuse QR from Admin page? 
// For now, simple redirect to device page where QR logic can live or just open details
const openQR = (device) => {
   // To keep it simple, let's just go to the detailed page
   // Or we can add the QR logic here if requested.
   // User asked for "scan karama show venna". 
   router.push(`/device/${device.id}`)
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.category-card {
  transition: transform 0.2s, box-shadow 0.2s;
  border-radius: 12px;
}
.category-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 12px rgba(0,0,0,0.1);
}
.no-shadow-border {
  box-shadow: none;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
}
.hover-bg:hover {
  background-color: #f8fafc;
}
</style>

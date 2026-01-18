<template>
  <q-page :class="$q.dark.isActive ? 'q-pa-md q-pa-lg-xl bg-dark text-white' : 'q-pa-md q-pa-lg-xl bg-grey-1'">
    <!-- Header Section -->
    <div class="row items-center justify-between q-mb-lg">
      <div class="column">
        <div class="text-h4 text-weight-bold text-dark q-mb-xs" style="letter-spacing: -1px">Dashboard</div>
        <div class="text-subtitle1 text-grey-6 flex items-center">
           <q-icon name="today" class="q-mr-xs" size="xs" />
           <span>Medical Equipment Overview</span>
        </div>
      </div>
      <div>
        <q-btn 
          color="primary" 
          icon="add" 
          label="Add Equipment" 
          no-caps 
          unelevated 
          class="q-mr-sm shadow-2"
          @click="openAddDialog"
          padding="8px 16px"
          style="border-radius: 8px"
        />
        <q-btn icon="refresh" flat round color="grey-7" @click="fetchData" />
      </div>
    </div>
    
    <div v-if="loading" class="row justify-center q-pa-lg">
       <q-spinner-dots color="primary" size="3em" />
    </div>

    <!-- View Mode: Categories -->
    <div v-else-if="viewMode === 'categories'">
       <div v-if="Object.keys(groupedDevices).length === 0" class="flex  flex-center column text-grey q-pa-xl">
          <q-icon name="dashboard_customize" size="4em" class="q-mb-md text-grey-4" />
          <div class="text-h6 text-grey-8">No equipment found.</div>
          <div class="text-grey-6">Get started by adding your first medical device!</div>
       </div>

       <div class="row q-col-gutter-lg">
          <div 
             v-for="(group, name) in groupedDevices" 
             :key="name" 
             class="col-12 col-sm-6 col-md-4 col-lg-3"
          >
             <q-card class="category-card cursor-pointer no-shadow" @click="selectCategory(name)">
                <q-card-section class="column items-start q-pa-lg">
                   <div class="row items-center justify-between full-width q-mb-md">
                     <q-avatar 
                       :color="getCategoryColor(name)" 
                       text-color="white" 
                       :icon="getCategoryIcon(name)" 
                       size="3.5em"
                       class="shadow-2 rounded-borders"
                       style="border-radius: 12px"
                     />
                     <q-badge color="grey-2" text-color="dark" rounded class="q-py-xs q-px-sm text-weight-bold">{{ group.length }} Units</q-badge>
                   </div>
                   
                   <div class="text-h6 text-weight-bold text-dark q-mb-xs">{{ name }}</div>
                   <div class="text-caption text-grey-6">Manage all {{ name.toLowerCase() }}s</div>
                </q-card-section>
                <q-separator color="grey-2" inset />
                <q-card-actions align="right" class="q-pa-sm">
                   <q-btn flat round color="primary" icon="arrow_forward" size="sm" />
                </q-card-actions>
             </q-card>
          </div>
       </div>
    </div>

    <!-- View Mode: Device List (Clean List) -->
    <div v-else-if="viewMode === 'list'">
       <div class="row items-center q-mb-lg">
          <q-btn flat round icon="arrow_back" color="dark" @click="viewMode = 'categories'" class="q-mr-sm" />
          <div class="column">
             <div class="text-h5 text-weight-bold text-dark">{{ selectedCategory }}</div>
             <div class="text-caption text-grey-6">{{ selectedDevices.length }} Devices found</div>
          </div>
       </div>

       <q-card class="bg-white no-shadow-border">
          <q-list separator>
             <q-item v-for="device in selectedDevices" :key="device.id" class="q-py-md hover-bg" clickable>
                <q-item-section avatar @click="router.push(`/device/${device.id}`)">
                  <q-avatar size="md" color="blue-1" text-color="primary" :icon="getCategoryIcon(selectedCategory)" />
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
      <q-card style="min-width: 400px; border-radius: 12px" :class="$q.dark.isActive ? 'bg-dark text-white' : ''">
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

    <!-- QR Code Dialog -->
    <q-dialog v-model="qrDialog">
      <q-card style="min-width: 300px; text-align: center" class="q-pa-lg rounded-borders">
        <div class="text-h6 font-weight-bold">{{ selectedDeviceForQR?.device_name }}</div>
        <div class="text-caption text-grey q-mb-md">{{ selectedDeviceForQR?.device_group }}</div>
        
        <img :src="qrCodeUrl" style="width: 200px; height: 200px" />
        
        <div class="q-mt-md">
           <q-btn label="Print" color="primary" flat icon="print" @click="printQR" />
           <q-btn label="Close" flat v-close-popup />
        </div>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { supabase } from '../supabase'
import { useQuasar } from 'quasar'
import QRCode from 'qrcode'

const $q = useQuasar()
const router = useRouter()
const route = useRoute()
const devices = ref([])
const loading = ref(true)

// Navigation State
const viewMode = ref('categories') // 'categories' or 'list'
const selectedCategory = ref('')

// Dialog State
const showDialog = ref(false)
const isEditMode = ref(false)
const categoryOptions = [
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
const formDevice = ref({
  id: null,
  device_name: '',
  device_id: '',
  device_group: '', 
  model_no: '',
  serial_no: ''
})

// QR Code State
const qrDialog = ref(false)
const selectedDeviceForQR = ref(null)
const qrCodeUrl = ref('')

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
    case 'CPAP': return 'air'
    case 'High Flow Machine': return 'air'
    case 'Centrifuge': return 'autorenew'
    case 'SpO2 Sensor': return 'sensors'
    case 'Concentrator': return 'filter_alt'
    default: return 'medical_services'
  }
}

const getCategoryColor = (category) => {
   switch(category) {
    case 'Cardiac Monitor': return 'red-8'
    case 'Infusion Pump': return 'blue-8'
    case 'Syringe Pump': return 'teal-8'
    case 'Ventilator': return 'cyan-8'
    case 'CPAP': return 'light-blue-9'
    case 'High Flow Machine': return 'indigo-8'
    case 'Centrifuge': return 'amber-9'
    case 'SpO2 Sensor': return 'pink-8'
    case 'Concentrator': return 'light-green-9'
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
  formDevice.value = { ...device }
  showDialog.value = true
}

const saveDevice = async () => {
  try {
    // Exclude metadata like created_at, id from update payload
    // We only want to update editable fields
    isEditMode.value ? $q.loading.show({ message: 'Updating...' }) : $q.loading.show({ message: 'Saving...' })

    const payload = {
      device_name: formDevice.value.device_name,
      device_group: formDevice.value.device_group,
      device_id: formDevice.value.device_id,
      model_no: formDevice.value.model_no,
      serial_no: formDevice.value.serial_no
    }

    if (isEditMode.value) {
      // Update
      const { error } = await supabase
        .from('devices')
        .update(payload)
        .eq('id', formDevice.value.id) // Use the ID from the form
      
      if (error) throw error
      $q.notify({ type: 'positive', message: 'Device updated' })
    } else {
      // Insert
      const { error } = await supabase.from('devices').insert([payload])
      if (error) throw error
      $q.notify({ type: 'positive', message: 'Device created' })
    }
    
    showDialog.value = false
    await fetchData()
  } catch (err) {
    console.error(err)
    $q.notify({ type: 'negative', message: 'Operation failed: ' + err.message })
  } finally {
    $q.loading.hide()
  }
}

const confirmDelete = (device) => {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete ${device.device_name}?`,
    cancel: true,
    persistent: true,
    ok: { label: 'Delete', color: 'negative', flat: true }
  }).onOk(async () => {
     $q.loading.show({ message: 'Deleting...' })
     
     // 1. Delete associated logs (Manual Cascade)
     // Use device_id (string) as that is the link in log tables
     if (device.device_id) {
        await supabase.from('charging_charts').delete().eq('device_id', device.device_id)
        await supabase.from('bme_charts').delete().eq('device_id', device.device_id)
     }

     // 2. Delete the device itself
     const { error } = await supabase.from('devices').delete().eq('id', device.id)
     
     $q.loading.hide()
     
     if (error) {
       console.error('Delete Error:', error)
       $q.notify({ type: 'negative', message: 'Delete failed: ' + error.message })
     } else {
       $q.notify({ type: 'positive', message: 'Device and logs deleted successfully' })
       await fetchData()
       
       // If the category is now empty, go back to main view
       if (selectedDevices.value.length === 0) { 
          viewMode.value = 'categories'
       }
     }
  })
}

const openQR = async (device) => {
  selectedDeviceForQR.value = device
  const url = `${window.location.origin}/device/${device.id}`
  try {
    qrCodeUrl.value = await QRCode.toDataURL(url)
    qrDialog.value = true
  } catch (err) {
    console.error(err)
    $q.notify({ type: 'negative', message: 'Failed to generate QR' })
  }
}

const printQR = () => {
  const win = window.open('', '', 'height=500, width=500');
  win.document.write('<html><body >');
  win.document.write(`<h2 style="text-align:center">${selectedDeviceForQR.value.device_name}</h2>`);
  win.document.write(`<div style="text-align:center"><img src="${qrCodeUrl.value}" /></div>`);
  win.document.write('</body></html>');
  win.document.close();
  win.print();
}

onMounted(async () => {
  await fetchData()
  
  if (route.query.category) {
    selectCategory(route.query.category)
  }
})

watch(() => route.query.category, (newVal) => {
  if (newVal) {
    selectCategory(newVal)
  } else {
    viewMode.value = 'categories'
  }
})
</script>

<style scoped>
.category-card {
  transition: all 0.2s ease;
  border-radius: 16px;
  border: 1px solid #EBF1F5;
  background: white;
}

/* Dark Mode Overrides */
.body--dark .category-card {
  background: #1d1d1d;
  border-color: #333;
}
.body--dark .device-card {
  background: #1d1d1d;
  border-color: #333;
}
.body--dark .bg-grey-1 {
  background-color: #121212 !important;
}
.body--dark .text-dark {
  color: #fff !important;
}

.category-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0,0,0,0.06) !important;
  border-color: #3B82F6;
}

.device-card {
  transition: all 0.2s ease;
  border-radius: 12px;
  border: 1px solid #F1F5F9;
  background: white;
}
.device-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0,0,0,0.04) !important;
  border-color: #94A3B8;
}

</style>

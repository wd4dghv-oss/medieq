<template>
  <q-page class="q-pa-md q-pa-lg-xl">
    <div class="row items-center justify-between q-mb-lg">
       <div>
          <div class="text-h4 text-weight-bold text-dark">Admin Portal</div>
          <div class="text-subtitle1 text-grey-7">Manage hospital equipment and logs</div>
       </div>
    </div>
    
    <q-card class="no-shadow-border bg-transparent">
      <q-tabs
        v-model="tab"
        dense
        class="text-grey-7 q-mb-md"
        active-color="primary"
        indicator-color="primary"
        align="left"
        narrow-indicator
        no-caps
        inline-label
      >
        <q-tab name="devices" label="Equipment Inventory" icon="medical_services" />
        <q-tab name="charts" label="Charging Logs" icon="battery_charging_full" />
      </q-tabs>

      <q-tab-panels v-model="tab" animated class="bg-transparent">
        
        <!-- Devices Tab -->
        <q-tab-panel name="devices" class="q-pa-none">
          <q-table
            :rows="devices"
            :columns="deviceColumns"
            row-key="id"
            :loading="loadingDevices"
            flat
            bordered
            class="bg-white rounded-borders"
            :filter="filter"
          >
            <template v-slot:top>
               <div class="text-h6 text-weight-bold q-mr-md">All Devices</div>
               <q-input dense debounce="300" v-model="filter" placeholder="Search" class="q-ml-md">
                 <template v-slot:prepend>
                   <q-icon name="search" />
                 </template>
               </q-input>
               <q-space />
               <q-btn 
                 color="primary" 
                 icon="add" 
                 label="Add New Equipment" 
                 no-caps 
                 unelevated 
                 @click="showAddDeviceDialog = true" 
               />
            </template>
            
            <template v-slot:body-cell-actions="props">
               <q-td :props="props">
                 <q-btn flat round icon="qr_code" color="primary" @click="showQRCode(props.row)">
                   <q-tooltip>View QR Code</q-tooltip>
                 </q-btn>
                 <q-btn flat round icon="visibility" color="grey-7" @click="viewDevice(props.row)" />
                 <q-btn flat round icon="delete" color="negative" @click="confirmDelete(props.row)" />
               </q-td>
            </template>
          </q-table>
        </q-tab-panel>

        <!-- Charts Tab (Placeholder for now) -->
        <q-tab-panel name="charts" class="q-pa-none">
          <q-card class="bg-white text-center q-pa-xl bounded-borders">
             <q-icon name="bar_chart" size="4em" color="grey-4" />
             <div class="text-h6 text-grey-6 q-mt-md">Analytics Overview</div>
             <div class="text-caption text-grey-5">Use the device details page to log charts.</div>
          </q-card>
        </q-tab-panel>

      </q-tab-panels>
    </q-card>

    <!-- Add Device Dialog -->
    <q-dialog v-model="showAddDeviceDialog" persistent>
      <q-card style="min-width: 400px; border-radius: 12px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6 text-weight-bold">Add Equipment</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-md">
          <q-form @submit="addDevice" class="q-gutter-md">
            <q-input filled v-model="newDevice.device_name" label="Device Name" hint="e.g. Bed 5 Monitor" :rules="[val => !!val || 'Required']" />
            
            <q-select 
              filled 
              v-model="newDevice.device_group" 
              :options="categoryOptions" 
              label="Category" 
              hint="Select Equipment Type"
              :rules="[val => !!val || 'Required']"
            />

            <q-input filled v-model="newDevice.device_id" label="Asset ID / Tag" :rules="[val => !!val || 'Required']" />
            
            <div class="row q-col-gutter-sm">
               <div class="col-6"><q-input filled v-model="newDevice.model_no" label="Model No" /></div>
               <div class="col-6"><q-input filled v-model="newDevice.serial_no" label="Serial No" /></div>
            </div>
            
            <div class="row justify-end q-mt-lg">
               <q-btn flat label="Cancel" color="grey-7" v-close-popup class="q-mr-sm" no-caps />
               <q-btn label="Save Equipment" type="submit" color="primary" unelevated no-caps />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- QR Code Dialog -->
    <q-dialog v-model="qrDialog">
      <q-card style="min-width: 300px; text-align: center" class="q-pa-lg rounded-borders">
        <div class="text-h6 font-weight-bold">{{ selectedDevice?.device_name }}</div>
        <div class="text-caption text-grey q-mb-md">{{ selectedDevice?.device_group }}</div>
        
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { supabase } from '../supabase'
import { useQuasar } from 'quasar'
import QRCode from 'qrcode'

const $q = useQuasar()
const router = useRouter()
const tab = ref('devices')
const devices = ref([])
const loadingDevices = ref(false)
const filter = ref('')

// Add Device
const showAddDeviceDialog = ref(false)
const categoryOptions = ['Cardiac Monitor', 'Infusion Pump', 'Syringe Pump', 'Ventilator', 'Other']
const newDevice = ref({
  device_name: '',
  device_id: '',
  device_group: '', // Used as Category
  model_no: '',
  serial_no: ''
})

// QR Code
const qrDialog = ref(false)
const selectedDevice = ref(null)
const qrCodeUrl = ref('')

const deviceColumns = [
  { name: 'device_name', label: 'Name', field: 'device_name', sortable: true, align: 'left' },
  { name: 'device_group', label: 'Category', field: 'device_group', sortable: true, align: 'left' },
  { name: 'device_id', label: 'Asset ID', field: 'device_id', sortable: true, align: 'left' },
  { name: 'model_no', label: 'Model', field: 'model_no', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'right' },
]

// --- Computed & Methods ---

const fetchDevices = async () => {
  loadingDevices.value = true
  const { data, error } = await supabase.from('devices').select('*').order('created_at', { ascending: false })
  if (error) {
    $q.notify({ type: 'negative', message: error.message })
  } else {
    devices.value = data
  }
  loadingDevices.value = false
}

const addDevice = async () => {
  const { error } = await supabase.from('devices').insert([newDevice.value])
  if (error) {
    $q.notify({ type: 'negative', message: error.message })
  } else {
    $q.notify({ type: 'positive', message: 'Equipment added successfully', position: 'top' })
    fetchDevices()
    newDevice.value = { device_name: '', device_id: '', device_group: '', model_no: '', serial_no: '' }
    showAddDeviceDialog.value = false
  }
}

const confirmDelete = (row) => {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete ${row.device_name}? This cannot be undone.`,
    cancel: true,
    persistent: true,
    ok: { label: 'Delete', color: 'negative', flat: true }
  }).onOk(async () => {
    const { error } = await supabase.from('devices').delete().eq('id', row.id)
    if (error) {
      $q.notify({ type: 'negative', message: error.message })
    } else {
      $q.notify({ type: 'positive', message: 'Device deleted' })
      fetchDevices()
    }
  })
}

const viewDevice = (row) => {
  router.push(`/device/${row.id}`)
}

const showQRCode = async (row) => {
  selectedDevice.value = row
  // The URL that the QR code points to. 
  // In production, change window.location.origin to your actual domain if needed.
  const url = `${window.location.origin}/device/${row.id}`
  
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
  win.document.write(`<h2 style="text-align:center">${selectedDevice.value.device_name}</h2>`);
  win.document.write(`<div style="text-align:center"><img src="${qrCodeUrl.value}" /></div>`);
  win.document.write('</body></html>');
  win.document.close();
  win.print();
}

onMounted(() => {
  fetchDevices()
})
</script>

<style scoped>
.rounded-borders {
  border-radius: 12px;
}
</style>

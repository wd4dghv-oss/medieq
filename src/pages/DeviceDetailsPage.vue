<template>
  <q-page class="q-pa-md">
    <div v-if="loading" class="flex flex-center" style="height: 80vh">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="!device" class="flex flex-center text-grey" style="height: 80vh">
      <div class="text-center">
        <q-icon name="error_outline" size="4em" />
        <div class="text-h6">Device not found</div>
      </div>
    </div>

    <div v-else class="row q-col-gutter-lg justify-center">
      
      <!-- Device Header Card -->
      <div class="col-12 col-md-8">
        <q-card class="bg-white rounded-borders">
          <q-card-section class="row items-center">
             <q-avatar size="lg" color="blue-1" text-color="primary" icon="medical_services" />
             <div class="q-ml-md">
               <div class="text-h5 text-weight-bold">{{ device.device_name }}</div>
               <div class="text-subtitle2 text-grey-7">{{ device.device_group }} • ID: {{ device.device_id }}</div>
             </div>
             <q-space />
             <q-badge :color="statusColor" :label="statusLabel" rounded class="q-px-sm q-py-xs" />
          </q-card-section>

          <q-separator />

          <!-- Quick Actions: "One Click Charge" -->
          <q-card-section class="q-py-lg text-center bg-grey-1">
             <div class="text-h6 q-mb-md">Quick Actions</div>
             <q-btn 
               push 
               color="primary" 
               size="xl" 
               icon="battery_charging_full" 
               label="Log Charging Now" 
               class="full-width"
               style="max-width: 400px"
               :loading="loggingCharge"
               @click="quickLogCharge"
             />
             <div class="q-mt-sm text-caption text-grey">Click to instantly record a charging start time</div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Logs Section -->
      <div class="col-12 col-md-8">
        <q-card class="bg-white rounded-borders">
          <q-tabs
            v-model="tab"
            dense
            class="text-grey"
            active-color="primary"
            indicator-color="primary"
            align="justify"
            narrow-indicator
          >
            <q-tab name="charging" label="Charging History" />
            <q-tab name="bme" label="BME Status Logs" />
          </q-tabs>

          <q-separator />

          <q-tab-panels v-model="tab" animated>
            
            <!-- Charging History -->
            <q-tab-panel name="charging">
               <q-table
                 :rows="chargingLogs"
                 :columns="chargingColumns"
                 row-key="id"
                 flat
                 :loading="loadingLogs"
               >
                 <template v-slot:top-right>
                    <q-btn flat round icon="refresh" @click="fetchLogs" />
                 </template>
               </q-table>
            </q-tab-panel>

            <!-- BME Logs -->
            <q-tab-panel name="bme">
               <div class="row justify-end q-mb-md">
                  <q-btn color="secondary" label="Add BME Log" icon="add" size="sm" @click="showAddBMEDialog = true" />
               </div>
               <q-table
                 :rows="bmeLogs"
                 :columns="bmeColumns"
                 row-key="id"
                 flat
                 :loading="loadingLogs"
               />
            </q-tab-panel>

          </q-tab-panels>
        </q-card>
      </div>

    </div>

    <!-- BME Dialog -->
    <q-dialog v-model="showAddBMEDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Log BME Status</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input filled v-model="newBME.reason" label="Reason/Status" class="q-mb-md" />
          <q-input filled v-model="newBME.send_date" type="date" label="Send Date" class="q-mb-md" />
          <q-input filled v-model="newBME.receive_date" type="date" label="Receive Date (Optional)" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn flat label="Save" color="primary" @click="addBMELog" />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { supabase } from '../supabase'
import { useQuasar, date } from 'quasar'

const $q = useQuasar()
const route = useRoute()
const deviceId = route.params.id // This is the ID from the URL (database ID)

const device = ref(null)
const loading = ref(true)
const loggingCharge = ref(false)
const tab = ref('charging')

// Logs
const chargingLogs = ref([])
const bmeLogs = ref([])
const loadingLogs = ref(false)

// BME Form
const showAddBMEDialog = ref(false)
const newBME = ref({
  reason: '',
  send_date: date.formatDate(Date.now(), 'YYYY-MM-DD'),
  receive_date: ''
})

const statusLabel = computed(() => 'Active') // Placeholder logic
const statusColor = computed(() => 'positive')

const chargingColumns = [
  { name: 'date', label: 'Date', field: 'charging_date', sortable: true, format: val => date.formatDate(val, 'YYYY-MM-DD') },
  { name: 'start', label: 'Start Time', field: 'charging_start', sortable: true },
  { name: 'end', label: 'End Time', field: 'charging_end' }
]

const bmeColumns = [
  { name: 'reason', label: 'Reason', field: 'reason', align: 'left' },
  { name: 'send', label: 'Sent', field: 'send_date', sortable: true },
  { name: 'receive', label: 'Received', field: 'receive_date' }
]

// --- Actions ---

const fetchDevice = async () => {
  try {
    const { data, error } = await supabase
      .from('devices')
      .select('*')
      .eq('id', deviceId)
      .single()
    
    if (error) throw error
    device.value = data
  } catch (err) {
    console.error(err)
    $q.notify({ type: 'negative', message: 'Failed to load device' })
  } finally {
    loading.value = false
  }
}

const fetchLogs = async () => {
  if (!device.value) return
  loadingLogs.value = true
  
  // Fetch Charging
  const { data: cLogs } = await supabase
    .from('charging_charts')
    .select('*')
    .eq('device_id', device.value.device_id) // Assuming linking by device_id string, not UUID PK for now
    .order('created_at', { ascending: false })
  
  if (cLogs) chargingLogs.value = cLogs

  // Fetch BME
  const { data: bLogs } = await supabase
    .from('bme_charts')
    .select('*')
    .eq('device_id', device.value.device_id)
    .order('created_at', { ascending: false })

  if (bLogs) bmeLogs.value = bLogs
  
  loadingLogs.value = false
}

const quickLogCharge = async () => {
  if (!device.value) return
  loggingCharge.value = true
  
  const now = new Date()
  const payload = {
    device_name: device.value.device_name,
    device_id: device.value.device_id,
    device_group: device.value.device_group,
    charging_date: date.formatDate(now, 'YYYY-MM-DD'),
    charging_start: date.formatDate(now, 'HH:mm:ss'),
    charging_end: null // Open ended
  }

  const { error } = await supabase.from('charging_charts').insert([payload])

  if (error) {
    $q.notify({ type: 'negative', message: error.message })
  } else {
    $q.notify({ type: 'positive', message: 'Charging started! Logged successfully.' })
    fetchLogs()
  }
  loggingCharge.value = false
}

const addBMELog = async () => {
  const payload = {
    device_name: device.value.device_name,
    device_id: device.value.device_id,
    device_group: device.value.device_group,
    reason: newBME.value.reason,
    send_date: newBME.value.send_date,
    receive_date: newBME.value.receive_date || null
  }
  
  const { error } = await supabase.from('bme_charts').insert([payload])
  
  if (error) {
    $q.notify({ type: 'negative', message: error.message })
  } else {
    $q.notify({ type: 'positive', message: 'BME Log added.' })
    showAddBMEDialog.value = false
    fetchLogs()
  }
}

onMounted(async () => {
  await fetchDevice()
  fetchLogs()
})

</script>

<style scoped>
.rounded-borders {
  border-radius: 16px;
}
</style>

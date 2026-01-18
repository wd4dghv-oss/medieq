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
                 <template v-slot:body-cell-actions="props">
                    <q-td :props="props">
                      <q-btn flat round dense icon="edit" color="primary" @click="openEditCharging(props.row)" />
                      <q-btn flat round dense icon="delete" color="negative" @click="confirmDeleteLog('charging_charts', props.row.id)" />
                    </q-td>
                 </template>
               </q-table>
            </q-tab-panel>

            <!-- BME Logs -->
            <q-tab-panel name="bme">
               <div class="row justify-end q-mb-md">
                  <q-btn color="secondary" label="Add BME Log" icon="add" size="sm" @click="openAddBME" />
               </div>
               <q-table
                 :rows="bmeLogs"
                 :columns="bmeColumns"
                 row-key="id"
                 flat
                 :loading="loadingLogs"
               >
                 <template v-slot:body-cell-status="props">
                    <q-td :props="props">
                      <q-chip 
                        :color="props.row.status === 'Fixed' ? 'positive' : 'warning'" 
                        text-color="white" 
                        dense 
                        square
                      >
                        {{ props.row.status || 'Pending' }}
                      </q-chip>
                    </q-td>
                 </template>
                 <template v-slot:body-cell-actions="props">
                    <q-td :props="props">
                      <q-btn flat round dense icon="edit" color="primary" @click="openEditBME(props.row)" />
                      <q-btn flat round dense icon="delete" color="negative" @click="confirmDeleteLog('bme_charts', props.row.id)" />
                    </q-td>
                 </template>
               </q-table>
            </q-tab-panel>

          </q-tab-panels>
        </q-card>
      </div>

    </div>

    <!-- BME Dialog (Add/Edit) -->
    <q-dialog v-model="showBMEDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">{{ isEditMode ? 'Edit BME Log' : 'Log BME Status' }}</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input filled v-model="formBME.reason" label="Reason/Status" class="q-mb-md" />
          
          <q-select 
            filled 
            v-model="formBME.status" 
            :options="['Pending', 'Fixed', 'Unrepairable', 'Service Completed']" 
            label="Repair Status" 
            class="q-mb-md" 
          />

          <q-input filled v-model="formBME.send_date" type="date" label="Send Date" class="q-mb-md" />
          <q-input filled v-model="formBME.receive_date" type="date" label="Receive Date (Optional)" stack-label hint="Update this when received" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn flat label="Save" color="primary" @click="saveBMELog" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Charging Edit Dialog -->
    <q-dialog v-model="showChargingDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Edit Charging Log</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input filled v-model="formCharging.charging_date" type="date" label="Date" class="q-mb-md" />
          <q-input filled v-model="formCharging.charging_start" type="time" label="Start Time" class="q-mb-md" />
          <q-input filled v-model="formCharging.charging_end" type="time" label="End Time" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn flat label="Save" color="primary" @click="saveChargingLog" />
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

// BME Form State
const showBMEDialog = ref(false)
const isEditMode = ref(false)
const formBME = ref({
  id: null,
  reason: '',
  status: 'Pending',
  send_date: '',
  receive_date: ''
})

// Charging Form State
const showChargingDialog = ref(false)
const formCharging = ref({
  id: null,
  charging_date: '',
  charging_start: '',
  charging_end: ''
})

const statusLabel = computed(() => 'Active') 
const statusColor = computed(() => 'positive')

const chargingColumns = [
  { name: 'date', label: 'Date', field: 'charging_date', sortable: true, format: val => date.formatDate(val, 'YYYY-MM-DD'), align: 'left' },
  { name: 'start', label: 'Start Time', field: 'charging_start', sortable: true, align: 'left' },
  { name: 'end', label: 'End Time', field: 'charging_end', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'right' }
]

const bmeColumns = [
  { name: 'reason', label: 'Reason', field: 'reason', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'left', sortable: true },
  { name: 'send', label: 'Sent', field: 'send_date', sortable: true, align: 'left' },
  { name: 'receive', label: 'Received', field: 'receive_date', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'right' }
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
    .eq('device_id', device.value.device_id) 
    .order('charging_date', { ascending: false })
    .order('charging_start', { ascending: false })
  
  if (cLogs) chargingLogs.value = cLogs

  // Fetch BME
  const { data: bLogs } = await supabase
    .from('bme_charts')
    .select('*')
    .eq('device_id', device.value.device_id)
    .order('send_date', { ascending: false })

  if (bLogs) bmeLogs.value = bLogs
  
  loadingLogs.value = false
}

const quickLogCharge = async () => {
  if (!device.value) return
  loggingCharge.value = true
  
  const now = new Date()
  const twoHoursLater = date.addToDate(now, { hours: 2 })
  const payload = {
    device_name: device.value.device_name,
    device_id: device.value.device_id,
    device_group: device.value.device_group,
    charging_date: date.formatDate(now, 'YYYY-MM-DD'),
    charging_start: date.formatDate(now, 'HH:mm:ss'),
    charging_end: date.formatDate(twoHoursLater, 'HH:mm:ss')
  }

  const { error } = await supabase.from('charging_charts').insert([payload])

  if (error) {
    $q.notify({ type: 'negative', message: error.message })
  } else {
    $q.notify({ type: 'positive', message: 'Charging started! (2 Hours timer set)' })
    fetchLogs()
  }
  loggingCharge.value = false
}

// --- BME CRUD ---

const openAddBME = () => {
  isEditMode.value = false
  formBME.value = {
    id: null,
    reason: '',
    status: 'Pending',
    send_date: date.formatDate(Date.now(), 'YYYY-MM-DD'),
    receive_date: ''
  }
  showBMEDialog.value = true
}

const openEditBME = (row) => {
  isEditMode.value = true
  formBME.value = { ...row }
  showBMEDialog.value = true
}

const saveBMELog = async () => {
  const payload = {
    device_name: device.value.device_name,
    device_id: device.value.device_id,
    device_group: device.value.device_group,
    reason: formBME.value.reason,
    status: formBME.value.status,
    send_date: formBME.value.send_date,
    receive_date: formBME.value.receive_date || null
  }
  
  let error = null
  if (isEditMode.value) {
     const { error: err } = await supabase.from('bme_charts').update(payload).eq('id', formBME.value.id)
     error = err
  } else {
     const { error: err } = await supabase.from('bme_charts').insert([payload])
     error = err
  }
  
  if (error) {
    $q.notify({ type: 'negative', message: error.message })
  } else {
    $q.notify({ type: 'positive', message: isEditMode.value ? 'BME Log updated' : 'BME Log added' })
    showBMEDialog.value = false
    fetchLogs()
  }
}

// --- Charging CRUD (Edit/Delete) ---

const openEditCharging = (row) => {
  formCharging.value = { ...row }
  showChargingDialog.value = true
}

const saveChargingLog = async () => {
  const payload = {
    charging_date: formCharging.value.charging_date,
    charging_start: formCharging.value.charging_start,
    charging_end: formCharging.value.charging_end
  }

  const { error } = await supabase.from('charging_charts').update(payload).eq('id', formCharging.value.id)
  
  if (error) {
    $q.notify({ type: 'negative', message: error.message })
  } else {
    $q.notify({ type: 'positive', message: 'Charging log updated' })
    showChargingDialog.value = false
    fetchLogs()
  }
}

const confirmDeleteLog = (table, id) => {
  $q.dialog({
    title: 'Confirm Delete',
    message: 'Are you sure you want to delete this log?',
    cancel: true,
    persistent: true,
    ok: { label: 'Delete', color: 'negative', flat: true }
  }).onOk(async () => {
    const { error } = await supabase.from(table).delete().eq('id', id)
    if (error) {
      $q.notify({ type: 'negative', message: error.message })
    } else {
      $q.notify({ type: 'positive', message: 'Log deleted' })
      fetchLogs()
    }
  })
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

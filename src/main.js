import { createApp } from 'vue'
import { Quasar, Notify, Loading, Dialog } from 'quasar'
import { createPinia } from 'pinia'
import router from './router'

// Import icon libraries
import '@quasar/extras/material-icons/material-icons.css'

// Import Quasar css
import 'quasar/src/css/index.sass'

import './style.css'
import App from './App.vue'

const pinia = createPinia()
const app = createApp(App)

app.use(Quasar, {
    plugins: {
        Notify,
        Loading,
        Dialog
    },
})

app.use(pinia)
app.use(router)

// Initialize auth store
import { useAuthStore } from './stores/auth'
const authStore = useAuthStore()
authStore.initialize().then(() => {
    app.mount('#app')
})


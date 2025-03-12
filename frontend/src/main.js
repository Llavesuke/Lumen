import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/fontawesome-custom.css'
import { initializeAuth } from './plugins/auth'
import './plugins/axios'

const app = createApp(App)
app.use(createPinia())

// Initialize auth store with stored token
initializeAuth()

app.use(router)

app.mount('#app')



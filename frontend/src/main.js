import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import App from './App.vue'
import { Icon } from '@iconify/vue'

const app = createApp(App)

app.component('Icon', Icon)

app.use(createPinia())
app.use(router)

app.mount('#app')

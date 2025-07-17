import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import { createI18n } from 'vue-i18n'

import messages from "./i18n/messages.json"

const i18n = createI18n({
    locale: 'de',
    messages: messages
})

const app = createApp(App)

app.use(i18n)

app.mount('#app')


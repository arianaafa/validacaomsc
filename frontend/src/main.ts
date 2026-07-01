import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

function initTheme(): void {
  const stored = localStorage.getItem('validamsc.theme')
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
  const theme = stored === 'light' || stored === 'dark' ? stored : prefersDark ? 'dark' : 'light'
  document.documentElement.classList.toggle('dark', theme === 'dark')
  document.documentElement.dataset.theme = theme
}

initTheme()

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')

// main.ts
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

import { createVuetify } from 'vuetify'
import 'vuetify/styles' 
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { createPinia } from 'pinia'

import '@mdi/font/css/materialdesignicons.css'

const vuetify = createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: {
      mdi,
    },
  },
})

createApp(App)
  .use(createPinia())
  .use(vuetify)
  .use(router)
  .mount('#app')

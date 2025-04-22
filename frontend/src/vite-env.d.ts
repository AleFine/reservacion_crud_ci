/// <reference types="vite/client" />

declare module 'vuetify/styles' {
  const styles: string
  export default styles
}

declare module 'vuetify/iconsets/mdi' {
  import { IconSet } from 'vuetify'
  export const mdi: IconSet
  export const aliases: Record<string, string>
}

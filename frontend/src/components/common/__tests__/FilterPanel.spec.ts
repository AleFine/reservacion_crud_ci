import { shallowMount } from '@vue/test-utils'
import FilterPanel from '../FilterPanel.vue'

describe('FilterPanel.vue', () => {
  const filtersConfig = [
    { key: 'searchTerm', component: 'div', props: { label: 'Buscar' }, visible: true },
    { key: 'date', component: 'div', props: { label: 'Fecha' }, visible: false }
  ]

  it('emite el evento "clear" al hacer clic en el botón de limpiar', async () => {
    const wrapper = shallowMount(FilterPanel, {
      props: {
        modelValue: true,
        filters: { searchTerm: 'abc', date: '2025-04-20' },
        itemsPerPage: 10,
        filtersConfig
      },
      global: {
        stubs: {
          'v-card': { template: '<div><slot /></div>' },
          'v-card-text': { template: '<div><slot /></div>' },
          'v-row': { template: '<div><slot /></div>' },
          'v-col': { template: '<div><slot /></div>' },
          'v-select': { template: '<div><slot /></div>' },
          'v-icon': { template: '<div><slot /></div>' },
          'v-btn': { template: '<button class="clear-btn"><slot /></button>' }
        }
      }
    })

    // buscamos el botón de limpiar y disparamos un evento de clic
    const clearBtn = wrapper.find('button.clear-btn')
    await clearBtn.trigger('click')
    expect(wrapper.emitted()).toHaveProperty('clear')
  })
})

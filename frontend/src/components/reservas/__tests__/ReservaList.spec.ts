import { shallowMount } from '@vue/test-utils'
import ReservaList from '../ReservaList.vue'
import type { Reserva } from '../../../types'

describe('ReservaList.vue', () => {
  const reservas: Reserva[] = [
    { id_reserva: 1, fecha: '2025-04-22', hora: '20:00', numero_de_personas: 4, id_comensal: 1, id_mesa: 2 }
  ]

  it('emite el evento "new" al hacer clic en el botón de nuevo', async () => {
    const wrapper = shallowMount(ReservaList, {
      props: { reservas, currentPage: 1, totalPages: 1, total: 1, loading: false },
      global: {
        stubs: {
          'v-card': { template: '<div><slot/></div>' },
          'v-card-item': { template: '<div><slot/></div>' },
          'v-card-text': { template: '<div><slot/></div>' },
          'v-card-title': { template: '<div><slot/></div>' },
          'v-progress-linear': { template: '<div></div>' },
          'v-table': { template: '<div><slot/></div>' },
          'v-pagination': { template: '<div></div>' },
          'v-icon': { template: '<span></span>' },
          'v-btn': { template: '<button><slot/></button>' }
        }
      }
    })
    const newBtn = wrapper.find('button')
    await newBtn.trigger('click')
    expect(wrapper.emitted()).toHaveProperty('new')
  })

  it('emite los eventos "edit" y "delete" con los datos correctos', async () => {
    const wrapper = shallowMount(ReservaList, {
      props: { reservas, currentPage: 1, totalPages: 1, total: 1, loading: false },
      global: {
        stubs: {
          'v-card': { template: '<div><slot/></div>' },
          'v-card-item': { template: '<div><slot/></div>' },
          'v-card-text': { template: '<div><slot/></div>' },
          'v-card-title': { template: '<div><slot/></div>' },
          'v-progress-linear': { template: '<div></div>' },
          'v-table': { template: '<div><slot/></div>' },
          'v-pagination': { template: '<div></div>' },
          'v-icon': { template: '<span></span>' },
          'v-btn': { template: '<button><slot/></button>' }
        }
      }
    })
    const btns = wrapper.findAll('button')
    // botón de editar es el segundo
    await btns[1].trigger('click')
    expect(wrapper.emitted('edit')![0]).toEqual([reservas[0]])
    // botón de eliminar es el tercero
    await btns[2].trigger('click')
    expect(wrapper.emitted('delete')![0]).toEqual([1])
  })
})
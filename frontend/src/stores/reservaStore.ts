import { defineStore } from 'pinia';
import { reservaService } from '../services/reservaService';
import type { Reserva, FilterOptions, PaginationOptions } from '../types';

export const useReservaStore = defineStore('reservas', {
  state: () => ({
    reservas: [] as Reserva[],
    selectedReserva: null as Reserva | null,
    loading: false,
    error: null as string | null,
    pagination: {
      page: 1,
      itemsPerPage: 10,
      totalItems: 0
    } as PaginationOptions,
    filters: {
      searchTerm: '',
      date: ''
    } as FilterOptions
  }),

  getters: {
    reservasWithDetails: (state) => state.reservas,
    totalPages: (state) => Math.ceil(state.pagination.totalItems / state.pagination.itemsPerPage)
  },

  actions: {
    async fetchReservas() {
      this.loading = true;
      try {
        const data = await reservaService.getAll(
          this.pagination.page, 
          this.pagination.itemsPerPage, 
          this.filters
        );
        this.reservas = data.data;
        this.pagination.totalItems = data.meta.total;
        this.error = null;
      } catch (error) {
        console.error('Error fetching reservas:', error);
        this.error = 'No se pudieron cargar las reservas';
      } finally {
        this.loading = false;
      }
    },

    async createReserva(reserva: Omit<Reserva, 'id_reserva'>) {
      this.loading = true;
      try {
        await reservaService.create(reserva);
        await this.fetchReservas();
        return true;
      } catch (error) {
        console.error('Error creating reserva:', error);
        this.error = 'No se pudo crear la reserva';
        return false;
      } finally {
        this.loading = false;
      }
    },

    async updateReserva(id_reserva: number, reserva: Partial<Reserva>) {
      this.loading = true;
      try {
        await reservaService.update(id_reserva, reserva);
        await this.fetchReservas();
        return true;
      } catch (error) {
        console.error('Error updating reserva:', error);
        this.error = 'No se pudo actualizar la reserva';
        return false;
      } finally {
        this.loading = false;
      }
    },

    async deleteReserva(id_reserva: number) {
      this.loading = true;
      try {
        await reservaService.delete(id_reserva);
        await this.fetchReservas();
        return true;
      } catch (error) {
        console.error('Error deleting reserva:', error);
        this.error = 'No se pudo eliminar la reserva';
        return false;
      } finally {
        this.loading = false;
      }
    },

    setPage(page: number) {
      this.pagination.page = page;
      this.fetchReservas();
    },

    setItemsPerPage(itemsPerPage: number) {
      this.pagination.itemsPerPage = itemsPerPage;
      this.pagination.page = 1;
      this.fetchReservas();
    },

    setFilters(filters: FilterOptions) {
      this.filters = { ...this.filters, ...filters };
      this.pagination.page = 1;
      this.fetchReservas();
    },

    clearFilters() {
      this.filters = {
        searchTerm: '',
        date: ''
      };
      this.pagination.page = 1;
      this.fetchReservas();
    }
  }
});
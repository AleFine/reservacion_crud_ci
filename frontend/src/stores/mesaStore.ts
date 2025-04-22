import { defineStore } from 'pinia';
import { mesaService } from '../services/mesaService';
import type { Mesa, FilterOptions, PaginationOptions } from '../types';

export const useMesaStore = defineStore('mesas', {
  state: () => ({
    mesas: [] as Mesa[],
    selectedMesa: null as Mesa | null,
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
    totalPages: (state) => Math.ceil(state.pagination.totalItems / state.pagination.itemsPerPage)
  },

  actions: {
    async fetchMesas() {
      this.loading = true;
      try {
        const data = await mesaService.getAll(
          this.pagination.page,
          this.pagination.itemsPerPage,
          this.filters
        );
        this.mesas = data.data;
        this.pagination.totalItems = data.meta.total;
        this.error = null;
      } catch (error) {
        console.error('Error fetching mesas:', error);
        this.error = 'No se pudieron cargar las mesas';
      } finally {
        this.loading = false;
      }
    },

    async createMesa(mesa: Omit<Mesa, 'id_mesa'>) {
      this.loading = true;
      try {
        await mesaService.create(mesa);
        await this.fetchMesas();
        return true;
      } catch (error) {
        console.error('Error creating mesa:', error);
        this.error = 'No se pudo crear la mesa';
        return false;
      } finally {
        this.loading = false;
      }
    },

    async updateMesa(id: number, mesa: Partial<Mesa>) {
      this.loading = true;
      try {
        await mesaService.update(id, mesa);
        await this.fetchMesas();
        return true;
      } catch (error) {
        console.error('Error updating mesa:', error);
        this.error = 'No se pudo actualizar la mesa';
        return false;
      } finally {
        this.loading = false;
      }
    },

    async deleteMesa(id: number) {
      this.loading = true;
      try {
        await mesaService.delete(id);
        await this.fetchMesas();
        return true;
      } catch (error) {
        console.error('Error deleting mesa:', error);
        this.error = 'No se pudo eliminar la mesa';
        return false;
      } finally {
        this.loading = false;
      }
    },

    setPage(page: number) {
      this.pagination.page = page;
      this.fetchMesas();
    },

    setItemsPerPage(itemsPerPage: number) {
      this.pagination.itemsPerPage = itemsPerPage;
      this.pagination.page = 1;
      this.fetchMesas();
    },

    setFilters(filters: FilterOptions) {
      this.filters = { ...this.filters, ...filters };
      this.pagination.page = 1;
      this.fetchMesas();
    },

    clearFilters() {
      this.filters = { searchTerm: '', date: '' };
      this.pagination.page = 1;
      this.fetchMesas();
    }
  }
});

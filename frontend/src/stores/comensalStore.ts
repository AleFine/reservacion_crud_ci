import { defineStore } from 'pinia';
import { comensalService } from '../services/comensalService';
import type { Comensal, FilterOptions, PaginationOptions } from '../types';

export const useComensalStore = defineStore('comensales', {
  state: () => ({
    comensales: [] as Comensal[],
    selectedComensal: null as Comensal | null,
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
    async fetchComensales() {
      this.loading = true;
      try {
        const data = await comensalService.getAll(
          this.pagination.page,
          this.pagination.itemsPerPage,
          this.filters
        );
        this.comensales = data.data;
        this.pagination.totalItems = data.meta.total;
        this.error = null;
      } catch (error) {
        console.error('Error fetching comensales:', error);
        this.error = 'No se pudieron cargar los comensales';
      } finally {
        this.loading = false;
      }
    },

    async createComensal(comensal: Omit<Comensal, 'id_comensal'>) {
      this.loading = true;
      try {
        await comensalService.create(comensal);
        await this.fetchComensales();
        return true;
      } catch (error) {
        console.error('Error creating comensal:', error);
        this.error = 'No se pudo crear el comensal';
        return false;
      } finally {
        this.loading = false;
      }
    },

    async updateComensal(id: number, comensal: Partial<Comensal>) {
      this.loading = true;
      try {
        await comensalService.update(id, comensal);
        await this.fetchComensales();
        return true;
      } catch (error) {
        console.error('Error updating comensal:', error);
        this.error = 'No se pudo actualizar el comensal';
        return false;
      } finally {
        this.loading = false;
      }
    },

    async deleteComensal(id: number) {
      this.loading = true;
      try {
        await comensalService.delete(id);
        await this.fetchComensales();
        return true;
      } catch (error) {
        console.error('Error deleting comensal:', error);
        this.error = 'No se pudo eliminar el comensal';
        return false;
      } finally {
        this.loading = false;
      }
    },

    setPage(page: number) {
      this.pagination.page = page;
      this.fetchComensales();
    },

    setItemsPerPage(itemsPerPage: number) {
      this.pagination.itemsPerPage = itemsPerPage;
      this.pagination.page = 1;
      this.fetchComensales();
    },

    setFilters(filters: FilterOptions) {
      this.filters = { ...this.filters, ...filters };
      this.pagination.page = 1;
      this.fetchComensales();
    },

    clearFilters() {
      this.filters = { searchTerm: '', date: '' };
      this.pagination.page = 1;
      this.fetchComensales();
    }
  }
});
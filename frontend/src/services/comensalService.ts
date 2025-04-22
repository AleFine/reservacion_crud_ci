import api from './api';
import type { Comensal } from '../types';

export const comensalService = {
  getAll: async (page = 1, perPage = 10, filters = {}) => {
    const response = await api.get('/comensales', {
      params: { page, per_page: perPage, ...filters }
    });
    return response.data;
  },

  getById: async (id: number) => {
    const response = await api.get(`/comensales/${id}`);
    return response.data;
  },

  create: async (comensal: Omit<Comensal, 'id_comensal'>) => {
    const response = await api.post('/comensales', comensal);
    return response.data;
  },

  update: async (id: number, comensal: Partial<Comensal>) => {
    const response = await api.put(`/comensales/${id}`, comensal);
    return response.data;
  },

  delete: async (id: number) => {
    const response = await api.delete(`/comensales/${id}`);
    return response.data;
  }
};
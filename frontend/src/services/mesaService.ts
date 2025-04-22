import api from './api';
import type { Mesa } from '../types';

export const mesaService = {
  getAll: async (page = 1, perPage = 10, filters = {}) => {
    const response = await api.get('/mesas', {
      params: { page, per_page: perPage, ...filters }
    });
    return response.data;
  },

  getById: async (id: number) => {
    const response = await api.get(`/mesas/${id}`);
    return response.data;
  },

  create: async (mesa: Omit<Mesa, 'id_mesa'>) => {
    const response = await api.post('/mesas', mesa);
    return response.data;
  },

  update: async (id: number, mesa: Partial<Mesa>) => {
    const response = await api.put(`/mesas/${id}`, mesa);
    return response.data;
  },

  delete: async (id: number) => {
    const response = await api.delete(`/mesas/${id}`);
    return response.data;
  }
};
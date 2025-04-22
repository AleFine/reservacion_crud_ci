import type { Component } from 'vue';

export interface Comensal {
  id_comensal?: number;
  nombre: string;
  correo: string;
  telefono?: string;
  direccion?: string;
  created_at?: string;
  updated_at?: string;
}

export interface Mesa {
  id_mesa?: number;
  numero_mesa: string;
  capacidad: number;
  ubicacion?: string;
  created_at?: string;
  updated_at?: string;
}

export interface Reserva {
  id_reserva?: number;
  fecha: string;
  hora: string;
  numero_de_personas: number;
  id_comensal: number;
  id_mesa: number;
  comensal?: Comensal;
  mesa?: Mesa;
  created_at?: string;
  updated_at?: string;
}

export interface PaginationOptions {
  page: number;
  itemsPerPage: number;
  totalItems: number;
}

export interface FilterConfig {
  key: keyof FilterOptions;
  component: Component;
  props: Record<string, unknown>;
  sm?: number;
  visible?: boolean;
}

export interface FilterOptions {
  searchTerm?: string;
  date?: string;
}
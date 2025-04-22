<template>
  <v-card>
    <v-card-item>
      <div class="d-flex justify-space-between align-center">
        <v-card-title class="text-h6">
          Comensales
          <span class="text-subtitle-2 text-medium-emphasis ml-2">
            {{ total }} {{ total === 1 ? 'resultado' : 'resultados' }}
          </span>
        </v-card-title>
        <v-btn color="primary" size="small" @click="$emit('new')">
          <v-icon size="small" class="mr-2">mdi-plus</v-icon>
          Nuevo Comensal
        </v-btn>
      </div>
    </v-card-item>
    <!-- Loading indicator -->
    <v-progress-linear v-if="loading" indeterminate color="primary"></v-progress-linear>
    <v-card-text>
      <v-table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in comensales" :key="c.id_comensal">
            <td>{{ c.id_comensal }}</td>
            <td>{{ c.nombre }}</td>
            <td>{{ c.correo }}</td>
            <td>{{ c.telefono || '-' }}</td>
            <td>{{ c.direccion || '-' }}</td>
            <td class="text-right">
              <v-btn icon variant="text" size="small" @click="$emit('edit', c)">
                <v-icon size="small">mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon variant="text" size="small" color="error" @click="$emit('delete', c.id_comensal)">
                <v-icon size="small">mdi-delete</v-icon>
              </v-btn>
            </td>
          </tr>
          <tr v-if="comensales.length === 0">
            <td colspan="6" class="text-center py-4 text-medium-emphasis">
              {{ loading ? 'Cargando comensales...' : 'No hay comensales disponibles' }}
            </td>
          </tr>
        </tbody>
      </v-table>
      <div v-if="totalPages > 1" class="mt-4 d-flex justify-center">
        <v-pagination
          :model-value="currentPage"
          :length="totalPages"
          :total-visible="7"
          @update:model-value="$emit('update:page', $event)"
        />
      </div>
    </v-card-text>
  </v-card>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import type { Comensal } from '../../types';

export default defineComponent({
  name: 'ComensalList',
  props: {
    comensales: { type: Array as () => Comensal[], required: true },
    currentPage: { type: Number, required: true },
    totalPages: { type: Number, required: true },
    total: { type: Number, required: true },
    loading: { type: Boolean, default: false }
  },
  emits: ['new', 'edit', 'delete', 'update:page'],
  setup() {
    return {};
  }
});
</script>
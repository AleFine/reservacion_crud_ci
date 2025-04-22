<template>
  <v-card>
    <v-card-item>
      <div class="d-flex justify-space-between align-center">
        <v-card-title class="text-h6">
          Mesas
          <span class="text-subtitle-2 text-medium-emphasis ml-2">
            {{ total }} {{ total === 1 ? 'resultado' : 'resultados' }}
          </span>
        </v-card-title>
        <v-btn color="primary" size="small" @click="$emit('new')">
          <v-icon size="small" class="mr-2">mdi-plus</v-icon>
          Nueva Mesa
        </v-btn>
      </div>
    </v-card-item>
    
    <v-progress-linear v-if="loading" indeterminate color="primary"></v-progress-linear>
    <v-card-text>
      <v-table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nº Mesa</th>
            <th>Capacidad</th>
            <th>Ubicación</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in mesas" :key="m.id_mesa">
            <td>{{ m.id_mesa }}</td>
            <td>{{ m.numero_mesa }}</td>
            <td>
              <div class="d-flex align-center">
                <v-icon size="small" class="mr-1">mdi-chair-rolling</v-icon>
                {{ m.capacidad }}
              </div>
            </td>
            <td>{{ m.ubicacion || '-' }}</td>
            <td class="text-right">
              <v-btn icon variant="text" size="small" @click="$emit('edit', m)">
                <v-icon size="small">mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon variant="text" size="small" color="error" @click="$emit('delete', m.id_mesa)">
                <v-icon size="small">mdi-delete</v-icon>
              </v-btn>
            </td>
          </tr>
          <tr v-if="mesas.length === 0">
            <td colspan="5" class="text-center py-4 text-medium-emphasis">
              {{ loading ? 'Cargando mesas...' : 'No hay mesas disponibles' }}
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
import type { Mesa } from '../../types';

export default defineComponent({
  name: 'MesaList',
  props: {
    mesas: { type: Array as () => Mesa[], required: true },
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
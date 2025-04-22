<template>
    <v-card>
      <v-card-item>
        <div class="d-flex justify-space-between align-center">
          <v-card-title class="text-h6">
            Reservas
            <span class="text-subtitle-2 text-medium-emphasis ml-2">
              {{ total }} {{ total === 1 ? "resultado" : "resultados" }}
            </span>
          </v-card-title>
          <v-btn color="primary" size="small" @click="$emit('new')">
            <v-icon size="small" class="mr-2">mdi-plus</v-icon>
            Nueva Reserva
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
              <th>Fecha</th>
              <th>Hora</th>
              <th>Personas</th>
              <th>Comensal</th>
              <th>Mesa</th>
              <th class="text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="reserva in reservas" :key="reserva.id_reserva">
              <td class="font-weight-medium">{{ reserva.id_reserva }}</td>
              <td>{{ formatDate(reserva.fecha) }}</td>
              <td>{{ reserva.hora }}</td>
              <td>
                <div class="d-flex align-center">
                  <v-icon size="small" class="mr-1">mdi-account-group</v-icon>
                  {{ reserva.numero_de_personas }}
                </div>
              </td>
              <td>
                <div v-if="reserva.comensal">
                  <div>{{ reserva.comensal.nombre }}</div>
                  <div class="text-caption text-medium-emphasis">{{ reserva.comensal.telefono }}</div>
                </div>
                <div v-else>ID: {{ reserva.id_comensal }}</div>
              </td>
              <td>
                <div v-if="reserva.mesa">
                  <div>Mesa {{ reserva.mesa.numero_mesa }}</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ reserva.mesa.capacidad }} personas · {{ reserva.mesa.ubicacion }}
                  </div>
                </div>
                <div v-else>ID: {{ reserva.id_mesa }}</div>
              </td>
              <td class="text-right">
                <div class="d-flex justify-end gap-2">
                  <v-btn icon variant="text" size="small" @click="$emit('edit', reserva)">
                    <v-icon size="small">mdi-pencil</v-icon>
                  </v-btn>
                  <v-btn 
                    icon 
                    variant="text" 
                    size="small" 
                    color="error" 
                    @click="$emit('delete', reserva.id_reserva)"
                  >
                    <v-icon size="small">mdi-delete</v-icon>
                  </v-btn>
                </div>
              </td>
            </tr>
            <tr v-if="reservas.length === 0">
              <td colspan="7" class="text-center py-4 text-medium-emphasis">
                {{ loading ? 'Cargando reservas...' : 'No hay reservas disponibles' }}
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
          ></v-pagination>
        </div>
      </v-card-text>
    </v-card>
  </template>
  
  <script lang="ts">
  import { defineComponent } from 'vue';
  import type { Reserva } from '../../types/index';
  import { format, parseISO } from 'date-fns';
  
  export default defineComponent({
    name: 'ReservaList',
    props: {
      reservas: {
        type: Array as () => Reserva[],
        required: true
      },
      currentPage: {
        type: Number,
        required: true
      },
      totalPages: {
        type: Number,
        required: true
      },
      total: {
        type: Number,
        required: true
      },
      loading: {
        type: Boolean,
        default: false
      }
    },
    emits: ['new', 'edit', 'delete', 'update:page'],
    setup() {
      const formatDate = (dateString: string) => {
        if (!dateString) return '';
        
        try {
          return format(parseISO(dateString), 'dd/MM/yyyy');
        } catch (e) {
          console.error('Error formatting date:', e);
          return dateString; 
        }
      };
  
      return {
        formatDate
      };
    }
  });
  </script>
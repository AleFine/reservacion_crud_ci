<template>
  <v-container class="max-width-1200">
    <div class="d-flex justify-space-between align-center mb-6">
      <h1 class="text-h4 font-weight-bold">Gestión de Reservas</h1>
      <div class="d-flex gap-2">
        <v-btn variant="outlined" size="small" @click="showFilters = !showFilters">
          <v-icon size="small" class="mr-2">mdi-filter-outline</v-icon>
          Filtros
        </v-btn>
        <v-btn v-if="activeTab === 'reservas'" color="primary" @click="handleNewReserva" hidden>
          <v-icon size="small" class="mr-2">mdi-plus</v-icon>
        </v-btn>
        <v-btn v-else-if="activeTab === 'comensales'" color="primary" @click="handleNewComensal" hidden>
        </v-btn>
        <v-btn v-else-if="activeTab === 'mesas'" color="primary" @click="handleNewMesa" hidden>
        </v-btn>
      </div>
    </div>

    <v-tabs v-model="activeTab" class="mb-6">
      <v-tab value="reservas">Reservas</v-tab>
      <v-tab value="comensales">Comensales</v-tab>
      <v-tab value="mesas">Mesas</v-tab>
    </v-tabs>

    <!-- FILTROS -->
    <filter-panel
      v-model="showFilters"
      :filters="activeTabFilters"
      :items-per-page="itemsPerPage"
      :filters-config="filtersConfig"
      @update:filters="updateFilters"
      @update:items-per-page="updateItemsPerPage"
      @clear="clearFilters"
    />

    <!-- Tabs -->
    <v-window v-model="activeTab">
      <!-- reserva tab -->
      <v-window-item value="reservas">
        <reserva-list
          :reservas="currentReservas"
          :current-page="reservaStore.pagination.page"
          :total-pages="reservaStore.totalPages"
          :total="totalReservas"
          :loading="reservaStore.loading"
          @new="handleNewReserva"
          @edit="handleEditReserva"
          @delete="handleDeleteReserva"
          @update:page="updateReservaPage"
        />
        
        <!-- resrva dialog -->
        <v-dialog v-model="showReservaDialog" max-width="600px">
          <reserva-form
            :initial-value="selectedReserva"
            :comensales="comensales"
            :mesas="mesas"
            :reservas="currentReservas"
            :loading="reservaStore.loading"
            @submit="saveReserva"
            @cancel="closeReservaDialog"
          />
        </v-dialog>
      </v-window-item>

      <!-- comensales tab -->
      <v-window-item value="comensales">
        <comensal-list
          :comensales="currentComensales"
          :current-page="comensalStore.pagination.page"
          :total-pages="totalComensalesPages"
          :total="totalComensales"
          :loading="comensalStore.loading"
          @new="handleNewComensal"
          @edit="handleEditComensal"
          @delete="handleDeleteComensal"
          @update:page="updateComensalPage"
        />
        
        <!-- comensal dialog -->
        <v-dialog v-model="showComensalDialog" max-width="500px">
          <comensal-form
            :initial-value="selectedComensal"
            :loading="comensalStore.loading"
            @submit="saveComensal"
            @cancel="closeComensalDialog"
          />
        </v-dialog>
      </v-window-item>

      <!-- mesas tab  -->
      <v-window-item value="mesas">
        <mesa-list
          :mesas="currentMesas"
          :current-page="mesaStore.pagination.page"
          :total-pages="totalMesasPages"
          :total="totalMesas"
          :loading="mesaStore.loading"
          @new="handleNewMesa"
          @edit="handleEditMesa"
          @delete="handleDeleteMesa"
          @update:page="updateMesaPage"
        />
        
        <!-- Mesa Dialog -->
        <v-dialog v-model="showMesaDialog" max-width="500px">
          <mesa-form
            :initial-value="selectedMesa"
            :loading="mesaStore.loading"
            @submit="saveMesa"
            @cancel="closeMesaDialog"
          />
        </v-dialog>
      </v-window-item>
    </v-window>

    <!-- confirmacion de delete -->
    <v-dialog v-model="showDeleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="text-h5">¿Estás seguro?</v-card-title>
        <v-card-text>
          Esta acción eliminará permanentemente el registro seleccionado. ¿Deseas continuar?
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn 
            variant="outlined" 
            @click="showDeleteDialog = false" 
            :disabled="isDeleting"
          >
            Cancelar
          </v-btn>
          <v-btn 
            color="error" 
            @click="confirmDelete" 
            :loading="isDeleting" 
            :disabled="isDeleting"
          >
            Eliminar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- snackbar par a notificaciones -->
    <v-snackbar v-model="showSnackbar" :color="snackbarColor" :timeout="3000">
      {{ snackbarMessage }}
      <template v-slot:actions>
        <v-btn variant="text" icon="mdi-close" @click="showSnackbar = false"></v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, computed, watch, onMounted } from 'vue';
import { useReservaStore } from '../stores/reservaStore';
import { useComensalStore } from '../stores/comensalStore';
import { useMesaStore } from '../stores/mesaStore';
import FilterPanel from '../components/common/FilterPanel.vue';
import ReservaList from '../components/reservas/ReservaList.vue';
import ReservaForm from '../components/reservas/ReservaForm.vue';
import ComensalList from '../components/comensales/ComensalList.vue';
import ComensalForm from '../components/comensales/ComensalForm.vue';
import MesaList from '../components/mesas/MesaList.vue';
import MesaForm from '../components/mesas/MesaForm.vue';
import type { Reserva, Comensal, Mesa, FilterOptions } from '../types';
import type { FilterConfig } from '../types';
import { markRaw } from 'vue';
import { VTextField } from 'vuetify/components';

const getFiltersConfig = (tab: string): FilterConfig[] => [
  {
    key: 'date',
    component: markRaw(VTextField),
    props: {
      label: 'Fecha',
      type: 'date',
      prependInnerIcon: 'mdi-calendar'
    },
    visible: tab === 'reservas',
    sm: 4
  },
  {
    key: 'searchTerm',
    component: markRaw(VTextField),
    props: {
      label: 'Buscar',
      placeholder: getSearchPlaceholder(tab),
      prependInnerIcon: 'mdi-magnify'
    },
    sm: 4
  }
];

const getSearchPlaceholder = (tab: string) => {
  const placeholders: Record<string, string> = {
    reservas: 'Nombre, mesa, teléfono...',
    comensales: 'Nombre, correo, teléfono...',
    mesas: 'Número de mesa, ubicación...'
  };
  return placeholders[tab] || 'Buscar...';
};

export default defineComponent({
  name: 'ReservasManager',
  components: {
    FilterPanel,
    ReservaList,
    ReservaForm,
    ComensalList,
    ComensalForm,
    MesaList,
    MesaForm
  },

  setup() {
    
    const filtersConfig = computed(() => 
      getFiltersConfig(activeTab.value)
    );

    // stores
    const reservaStore = useReservaStore();
    const comensalStore = useComensalStore(); 
    const mesaStore = useMesaStore();

    // state
    const activeTab = ref('reservas');
    const showFilters = ref(false);
    const itemsPerPage = ref(10);

    // dialogs
    const showReservaDialog = ref(false);
    const showComensalDialog = ref(false);
    const showMesaDialog = ref(false);
    const showDeleteDialog = ref(false);

    // items seleccionados
    const selectedReserva = ref<Reserva | null>(null);
    const selectedComensal = ref<Comensal | null>(null);
    const selectedMesa = ref<Mesa | null>(null);
    const itemToDelete = ref<{ type: string; id: number } | null>(null);

    // snachbars 
    const showSnackbar = ref(false);
    const snackbarMessage = ref('');
    const snackbarColor = ref('success');

    // props computadas
    const currentReservas = computed(() => reservaStore.reservas);
    const totalReservas = computed(() => reservaStore.pagination.totalItems);
    
    const currentComensales = computed(() => comensalStore.comensales);
    const totalComensales = computed(() => comensalStore.pagination.totalItems);
    const totalComensalesPages = computed(() => comensalStore.totalPages);
    
    const currentMesas = computed(() => mesaStore.mesas);
    const totalMesas = computed(() => mesaStore.pagination.totalItems);
    const totalMesasPages = computed(() => mesaStore.totalPages);

    const comensales = computed(() => comensalStore.comensales);
    const mesas = computed(() => mesaStore.mesas);

    // filtros activos basado en el tab activo
    const activeTabFilters = computed(() => {
      switch (activeTab.value) {
        case 'reservas':
          return reservaStore.filters;
        case 'comensales':
          return comensalStore.filters;
        case 'mesas':
          return mesaStore.filters;
        default:
          return {};
      }
    });

    // metdos - init
    onMounted(async () => {
      await Promise.all([
        reservaStore.fetchReservas(),
        comensalStore.fetchComensales(),
        mesaStore.fetchMesas()
      ]);
    });

    // Watch para cargar datos al cambiar de tab
    watch(activeTab, (newTab) => {
      switch (newTab) {
        case 'reservas':
          if (currentReservas.value.length === 0 && !reservaStore.loading) {
            reservaStore.fetchReservas();
          }
          break;
        case 'comensales':
          if (currentComensales.value.length === 0 && !comensalStore.loading) {
            comensalStore.fetchComensales();
          }
          break;
        case 'mesas':
          if (currentMesas.value.length === 0 && !mesaStore.loading) {
            mesaStore.fetchMesas();
          }
          break;
      }
    });

    // metodos - filtros
    const updateFilters = (filters: FilterOptions) => {
      switch (activeTab.value) {
        case 'reservas':
          reservaStore.setFilters(filters);
          break;
        case 'comensales':
          comensalStore.setFilters(filters);
          break;
        case 'mesas':
          mesaStore.setFilters(filters);
          break;
      }
    };

    const updateItemsPerPage = (perPage: number) => {
      itemsPerPage.value = perPage;
      switch (activeTab.value) {
        case 'reservas':
          reservaStore.setItemsPerPage(perPage);
          break;
        case 'comensales':
          comensalStore.setItemsPerPage(perPage);
          break;
        case 'mesas':
          mesaStore.setItemsPerPage(perPage);
          break;
      }
    };

    const clearFilters = () => {
      switch (activeTab.value) {
        case 'reservas':
          reservaStore.clearFilters();
          break;
        case 'comensales':
          comensalStore.clearFilters();
          break;
        case 'mesas':
          mesaStore.clearFilters();
          break;
      }
    };

    // metodos - paginación
    const updateReservaPage = (page: number) => reservaStore.setPage(page);
    const updateComensalPage = (page: number) => comensalStore.setPage(page);
    const updateMesaPage = (page: number) => mesaStore.setPage(page);

    // metodos - Reservas
    const handleNewReserva = () => {
      selectedReserva.value = null;
      showReservaDialog.value = true;
    };

    const handleEditReserva = (reserva: Reserva) => {
      selectedReserva.value = { ...reserva };
      showReservaDialog.value = true;
    };

    const handleDeleteReserva = (id: number) => {
      itemToDelete.value = { type: 'reserva', id };
      showDeleteDialog.value = true;
    };

    const closeReservaDialog = () => {
      showReservaDialog.value = false;
      selectedReserva.value = null;
    };

    

    const saveReserva = async (reserva: Partial<Reserva>) => {
      try {
        if (reserva.id_reserva) {
          // actualizar reserva
          const success = await reservaStore.updateReserva(reserva.id_reserva, reserva);
          if (success) {
            showNotification('Reserva actualizada correctamente', 'success');
            closeReservaDialog();
          } else {
            showNotification('Error al actualizar la reserva', 'error');
          }
        } else {
          // nueva reserva
          const success = await reservaStore.createReserva(reserva as Omit<Reserva, 'id_reserva'>);
          if (success) {
            showNotification('Reserva creada correctamente', 'success');
            closeReservaDialog();
          } else {
            showNotification('Error al crear la reserva', 'error');
          }
        }
      } catch (error) {
        
        console.error('Error saving reserva:', error);
        showNotification('Error al guardar la reserva', 'error');
      }
    };

    // metodos - Comensales
    const handleNewComensal = () => {
      selectedComensal.value = null;
      showComensalDialog.value = true;
    };

    const handleEditComensal = (comensal: Comensal) => {
      selectedComensal.value = { ...comensal };
      showComensalDialog.value = true;
    };

    const handleDeleteComensal = (id: number) => {
      itemToDelete.value = { type: 'comensal', id };
      showDeleteDialog.value = true;
    };

    const closeComensalDialog = () => {
      showComensalDialog.value = false;
      selectedComensal.value = null;
    };

    const saveComensal = async (comensal: Partial<Comensal>) => {
      try {
        if (comensal.id_comensal) {
          const success = await comensalStore.updateComensal(comensal.id_comensal, comensal);
          if (success) {
            await reservaStore.fetchReservas();
            showNotification('Comensal actualizado correctamente', 'success');
            closeComensalDialog();
          } else {
            showNotification('Error al actualizar el comensal', 'error');
          }
        } else {
          const success = await comensalStore.createComensal(comensal as Omit<Comensal, 'id_comensal'>);
          if (success) {
            await reservaStore.fetchReservas();
            showNotification('Comensal creado correctamente', 'success');
            closeComensalDialog();
          } else {
            showNotification('Error al crear el comensal', 'error');
          }
        }
      } catch (error) {
        console.error('Error saving comensal:', error);
        showNotification('Error al guardar el comensal', 'error');
      }
    };

    // metodos - mesas
    const handleNewMesa = () => {
      selectedMesa.value = null;
      showMesaDialog.value = true;
    };

    const handleEditMesa = (mesa: Mesa) => {
      selectedMesa.value = { ...mesa };
      showMesaDialog.value = true;
    };

    const handleDeleteMesa = (id: number) => {
      itemToDelete.value = { type: 'mesa', id };
      showDeleteDialog.value = true;
    };

    const closeMesaDialog = () => {
      showMesaDialog.value = false;
      selectedMesa.value = null;
    };

    const saveMesa = async (mesa: Partial<Mesa>) => {
      try {
        if (mesa.id_mesa) {
          const success = await mesaStore.updateMesa(mesa.id_mesa, mesa);
          if (success) {
            await reservaStore.fetchReservas();
            showNotification('Mesa actualizada correctamente', 'success');
            closeMesaDialog();
          } else {
            showNotification('Error al actualizar la mesa', 'error');
          }
        } else {
          const success = await mesaStore.createMesa(mesa as Omit<Mesa, 'id_mesa'>);
          if (success) {
            await reservaStore.fetchReservas();
            showNotification('Mesa creada correctamente', 'success');
            closeMesaDialog();
          } else {
            showNotification('Error al crear la mesa', 'error');
          }
        }
      } catch (error) {
        console.error('Error saving mesa:', error);
        showNotification('Error al guardar la mesa', 'error');
      }
    };

    const isDeleting = ref(false);

    const confirmDelete = async () => {
      if (!itemToDelete.value) return;

      const { type, id } = itemToDelete.value;
      let success = false;
      
      // indicador de carga
      isDeleting.value = true;

      try {
        switch (type) {
          case 'reserva':
            success = await reservaStore.deleteReserva(id);
            if (success) {
              showNotification('Reserva eliminada correctamente', 'success');
            } else {
              showNotification('Error al eliminar la reserva', 'error');
            }
            break;
          case 'comensal':
            success = await comensalStore.deleteComensal(id);
            if (success) {
              await reservaStore.fetchReservas();
              showNotification('Comensal eliminado correctamente', 'success');
            } else {
              showNotification('Error al eliminar el comensal', 'error');
            }
            break;
          case 'mesa':
            success = await mesaStore.deleteMesa(id);
            if (success) {
              await reservaStore.fetchReservas();
              showNotification('Mesa eliminada correctamente', 'success');
            } else {
              showNotification('Error al eliminar la mesa', 'error');
            }
            break;
        }
        
        if (success) {
          showDeleteDialog.value = false;
          itemToDelete.value = null;
        }
      } catch (error) {
        console.error(`Error deleting ${type}:`, error);
        showNotification(`Error al eliminar ${type}`, 'error');
      } finally {
        isDeleting.value = false;
      }
    };

    // metodo para mostrar notificaciones
    const showNotification = (message: string, color: 'success' | 'error' | 'info' | 'warning') => {
      snackbarMessage.value = message;
      snackbarColor.value = color;
      showSnackbar.value = true;
    };

    return {
      // stores
      reservaStore,
      comensalStore,
      mesaStore,
      
      // state
      activeTab,
      showFilters,
      itemsPerPage,
      
      // dialogs
      showReservaDialog,
      showComensalDialog,
      showMesaDialog,
      showDeleteDialog,
      
      // items seleccionados
      selectedReserva,
      selectedComensal,
      selectedMesa,
      itemToDelete,
      
      // snackbars
      showSnackbar,
      snackbarMessage,
      snackbarColor,
      
      // props computadas
      currentReservas,
      totalReservas,
      currentComensales,
      totalComensales,
      totalComensalesPages,
      currentMesas,
      totalMesas,
      totalMesasPages,
      comensales,
      mesas,
      activeTabFilters,
      
      // metodos - filtros
      updateFilters,
      updateItemsPerPage,
      clearFilters,
      
      // metodos - paginación
      updateReservaPage,
      updateComensalPage,
      updateMesaPage,
      
      // metodos - reservas
      handleNewReserva,
      handleEditReserva,
      handleDeleteReserva,
      closeReservaDialog,
      saveReserva,
      
      // metodos - comensales
      handleNewComensal,
      handleEditComensal,
      handleDeleteComensal,
      closeComensalDialog,
      saveComensal,
      
      // metodos - mesas
      handleNewMesa,
      handleEditMesa,
      handleDeleteMesa,
      closeMesaDialog,
      saveMesa,
      
      // confirmacion de delete
      confirmDelete,
      
      // notificacion
      showNotification,

      filtersConfig,

      isDeleting
    };
  }
});
</script>

<style scoped>
.max-width-1200 {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
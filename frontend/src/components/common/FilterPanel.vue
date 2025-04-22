<template>
  <v-card v-if="modelValue" class="mb-4">
    <v-card-text class="pt-6">
      <v-row>
        <!-- filtros dinámicos (dentro del crud de comensales o mesas no aparecerá el filtro de fecha) -->
        <template v-for="filterConfig in activeFiltersConfig" :key="filterConfig.key">
          <v-col cols="12" :sm="filterConfig.sm || 4">
            <component
              :is="filterConfig.component"
              v-bind="filterConfig.props"
              :model-value="filters[filterConfig.key]"
              @update:model-value="updateFilter(filterConfig.key, $event)"
            />
          </v-col>
        </template>

        <v-col cols="12" sm="2" class="d-flex align-center">
          <v-btn variant="text" @click="$emit('clear')">
            <v-icon size="small" class="mr-2">mdi-close</v-icon>
            Limpiar Busqueda
          </v-btn>
        </v-col>
        <v-col cols="12" sm="2">
          <v-select
            :model-value="itemsPerPage"
            label="Por página"
            :items="[5, 10, 20, 50]"
            @update:model-value="$emit('update:itemsPerPage', $event)"
          ></v-select>
        </v-col>

      </v-row>
    </v-card-text>
  </v-card>
</template>

<script lang="ts">
import { defineComponent, computed } from 'vue';
import type { PropType } from 'vue';
import type { FilterConfig, FilterOptions } from '../../types';

export default defineComponent({
  name: 'FilterPanel',
  props: {
    modelValue: { type: Boolean, default: false },
    filters: { type: Object as () => FilterOptions, required: true },
    itemsPerPage: { type: Number, default: 10 },
    filtersConfig: { 
      type: Array as PropType<FilterConfig[]>,
      required: true 
    }
  },
  emits: ['update:modelValue', 'update:filters', 'update:itemsPerPage', 'clear'],
  
  setup(props, { emit }) {
    const activeFiltersConfig = computed(() => 
      props.filtersConfig.filter(config => config.visible !== false)
    );

    const updateFilter = (key: keyof FilterOptions, value: unknown) => {
      const updatedFilters = { ...props.filters, [key]: value };
      emit('update:filters', updatedFilters);
    };

    return { activeFiltersConfig, updateFilter };
  }
});
</script>
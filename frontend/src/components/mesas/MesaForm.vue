<template>
  <v-card>
    <v-card-title>
      {{ isEdit ? 'Editar Mesa' : 'Nueva Mesa' }}
      <v-card-subtitle>
        {{ isEdit ? 'Modifica los datos de la mesa existente' : 'Complete los datos para crear una nueva mesa' }}
      </v-card-subtitle>
    </v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="form.numero_mesa"
            label="Número de mesa"
            type="text"
            prepend-inner-icon="mdi-table"
            :rules="[rules.required]"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model.number="form.capacidad"
            label="Capacidad"
            type="number"
            min="1"
            prepend-inner-icon="mdi-account-group"
            :rules="[rules.required, rules.minValue(1)]"
          />
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="12">
          <v-text-field
            v-model="form.ubicacion"
            label="Ubicación"
            prepend-inner-icon="mdi-map-marker"
          />
        </v-col>
      </v-row>
    </v-card-text>
    <v-card-actions>
      <v-spacer />
      <v-btn variant="outlined" @click="$emit('cancel')">Cancelar</v-btn>
      <v-btn
        color="primary"
        @click="submit"
        :loading="loading"
        :disabled="!isFormValid"
      >
        {{ isEdit ? 'Guardar cambios' : 'Crear mesa' }}
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script lang="ts">
import { defineComponent, ref, computed, watch } from 'vue';
import type { Mesa } from '../../types';

export default defineComponent({
  name: 'MesaForm',
  props: {
    initialValue: {
      type: Object as () => Mesa | null,
      default: null
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['submit', 'cancel'],
  setup(props, { emit }) {
    const form = ref({
      numero_mesa: '',
      capacidad: 1,
      ubicacion: ''
    });
    const isEdit = computed(() => !!props.initialValue);
    const rules = {
      required: (v: unknown) => (!!v || v === 0) || 'Este campo es requerido',
      minValue: (min: number) => (v: number) => v >= min || `El valor debe ser >= ${min}`
    };
    const isFormValid = computed(
      () =>
        !!form.value.numero_mesa &&
        rules.required(form.value.capacidad) === true &&
        rules.minValue(1)(form.value.capacidad) === true
    );

    watch(
      () => props.initialValue,
      (val) => {
        if (val) {
          form.value = {
            numero_mesa: val.numero_mesa || '',
            capacidad: val.capacidad,
            ubicacion: val.ubicacion || ''
          };
        } else {
          form.value = { numero_mesa: '', capacidad: 1, ubicacion: '' };
        }
      },
      { immediate: true }
    );

    const submit = () => {
      if (!isFormValid.value) return;
      const mesaData: Partial<Mesa> = {
        numero_mesa: form.value.numero_mesa,
        capacidad: Number(form.value.capacidad),
        ubicacion: form.value.ubicacion || undefined
      };
      if (props.initialValue?.id_mesa) {
        mesaData.id_mesa = props.initialValue.id_mesa;
      }
      emit('submit', mesaData);
    };

    return { form, isEdit, rules, isFormValid, submit };
  }
});
</script>

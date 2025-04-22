<template>
  <v-card>
    <v-card-title>
      {{ isEdit ? 'Editar Comensal' : 'Nuevo Comensal' }}
      <v-card-subtitle>
        {{ isEdit ? 'Modifica los datos del comensal existente' : 'Complete los datos para crear un nuevo comensal' }}
      </v-card-subtitle>
    </v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="form.nombre"
            label="Nombre"
            prepend-inner-icon="mdi-account"
            :rules="[rules.required]"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="form.correo"
            label="Correo"
            prepend-inner-icon="mdi-email"
            :rules="[rules.required, rules.email]"
          />
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="form.telefono"
            label="Teléfono"
            prepend-inner-icon="mdi-phone"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="form.direccion"
            label="Dirección"
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
        {{ isEdit ? 'Guardar cambios' : 'Crear comensal' }}
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script lang="ts">
import { defineComponent, ref, computed, watch } from 'vue';
import type { Comensal } from '../../types';

export default defineComponent({
  name: 'ComensalForm',
  props: {
    initialValue: {
      type: Object as () => Comensal | null,
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
      nombre: '',
      correo: '',
      telefono: '',
      direccion: ''
    });
    const isEdit = computed(() => !!props.initialValue);
    const isFormValid = computed(
      () =>
        !!form.value.nombre &&
        !!form.value.correo &&
        rules.email(form.value.correo) === true
    );
    const rules = {
      required: (v: unknown) => !!v || 'Este campo es requerido',
      email: (v: string) =>
        !!v.match(/\S+@\S+\.\S+/) || 'Correo inválido'
    };

    watch(
      () => props.initialValue,
      (val) => {
        if (val) {
          form.value = {
            nombre: val.nombre,
            correo: val.correo,
            telefono: val.telefono || '',
            direccion: val.direccion || ''
          };
        } else {
          form.value = {
            nombre: '',
            correo: '',
            telefono: '',
            direccion: ''
          };
        }
      },
      { immediate: true }
    );

    const submit = () => {
      if (!isFormValid.value) return;
      const comensalData: Partial<Comensal> = {
        nombre: form.value.nombre,
        correo: form.value.correo,
        telefono: form.value.telefono || undefined,
        direccion: form.value.direccion || undefined
      };
      if (props.initialValue?.id_comensal) {
        comensalData.id_comensal = props.initialValue.id_comensal;
      }
      emit('submit', comensalData);
    };

    return { form, isEdit, isFormValid, rules, submit };
  }
});
</script>

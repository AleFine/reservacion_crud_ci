<template>
    <v-card>
      <v-card-title>
        {{ isEdit ? "Editar Reserva" : "Nueva Reserva" }}
        <v-card-subtitle>
          {{ isEdit ? "Modifica los datos de la reserva existente" : "Complete los datos para crear una nueva reserva" }}
        </v-card-subtitle>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.fecha"
              label="Fecha"
              type="date"
              prepend-inner-icon="mdi-calendar"
              :rules="[rules.required]"
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.hora"
              label="Hora"
              type="time"
              prepend-inner-icon="mdi-clock-outline"
              :rules="[rules.required]"
            ></v-text-field>
          </v-col>
        </v-row>
  
        <v-autocomplete
          v-model="form.id_comensal"
          :items="comensales"
          item-title="nombre"
          item-value="id_comensal"
          label="Comensal"
          placeholder="Seleccionar comensal"
          prepend-inner-icon="mdi-account"
          :rules="[rules.required]"
          return-object
        >
          <template v-slot:item="{ item, props }">
            <v-list-item v-bind="props">
              <v-list-item-subtitle>{{ item.raw.telefono || item.raw.correo }}</v-list-item-subtitle>
            </v-list-item>
          </template>
        </v-autocomplete>
  
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.numero_de_personas"
              label="Número de personas"
              type="number"
              min="1"
              prepend-inner-icon="mdi-account-group"
              :key="(forceValidation)"
              :rules="[
                rules.required, 
                rules.minValue(1),
                rules.capacidadMesa() 
              ]"
            ></v-text-field>
            
          </v-col>
          
          <v-col cols="12" sm="6">
            <v-autocomplete
              v-model="form.id_mesa"
              :items="mesas"
              item-title="numero_mesa"
              item-value="id_mesa"
              label="Mesa"
              placeholder="Seleccionar mesa"
              prepend-inner-icon="mdi-table-furniture"
              :rules="[rules.required, rules.mesaDisponible()]"
              return-object
              :key="forceValidation"
              :error-messages="mesaError"
              @update:model-value="checkMesaDisponible"
            >
              <template v-slot:item="{ item, props }">
                <v-list-item v-bind="props">
                  <v-list-item-subtitle>
                    {{ item.raw.capacidad }} personas {{ item.raw.ubicacion ? `· ${item.raw.ubicacion}` : '' }}
                  </v-list-item-subtitle>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-col>
        </v-row>
        
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn variant="outlined" @click="$emit('cancel')">Cancelar</v-btn>
        <v-btn color="primary" @click="submit" :loading="loading" :disabled="!isFormValid">
          {{ isEdit ? "Guardar cambios" : "Crear reserva" }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </template>
  
  <script lang="ts">
  import { defineComponent, ref, computed, watch } from 'vue';
  import type { Reserva, Comensal, Mesa } from '../../types';

  export default defineComponent({
    name: 'ReservaForm',
    props: {
      initialValue: {
        type: Object as () => Reserva | null,
        default: null
      },
      comensales: {
        type: Array as () => Comensal[],
        required: true
      },
      mesas: {
        type: Array as () => Mesa[],
        required: true
      },
      loading: {
        type: Boolean,
        default: false
      },
      reservas: {
        type: Array as () => Reserva[],
        required: true
      },
    },
    emits: ['submit', 'cancel'],
    setup(props, { emit }) {
      const forceValidation = ref(0);
      const form = ref({
        fecha: new Date().toISOString().split('T')[0],
        hora: '20:00',
        numero_de_personas: 2,
        id_comensal: null as Comensal | null,
        id_mesa: null as Mesa | null
      });

      watch(() => form.value.id_mesa, () => {
        forceValidation.value++;
      });

      watch([() => form.value.fecha, () => form.value.hora], () => {
        forceValidation.value++; 
      });

      const mesaError = ref('');

      const checkMesaDisponible = () => {
        if (!form.value.id_mesa) return;
        
        const disponible = isMesaDisponible(
          form.value.fecha,
          form.value.hora,
          form.value.id_mesa.id_mesa,
          props.initialValue?.id_reserva
        );
        
        mesaError.value = disponible ? '' : 'Mesa no disponible';
      };

      const isEdit = computed(() => !!props.initialValue);
      const isFormValid = computed(() => {
        const personasValidas = form.value.id_mesa 
          ? form.value.numero_de_personas <= form.value.id_mesa.capacidad
          : false;

        const mesaDisponible = form.value.id_mesa
          ? isMesaDisponible(
              form.value.fecha,
              form.value.hora,
              form.value.id_mesa!.id_mesa,
              props.initialValue?.id_reserva
            )
          : false;
        return !!form.value.fecha && 
              !!form.value.hora && 
              !!form.value.numero_de_personas && 
              !!form.value.id_comensal &&
              !!form.value.id_mesa &&
              personasValidas &&
              mesaDisponible; 
      });

      const isMesaDisponible = (fecha: string, hora: string, idMesa?: number, idReserva?: number): boolean => {
        if (!fecha || !hora || !idMesa) return true;
        
        // Normalizar formato de hora (HH:mm)
        const horaNormalizada = hora.substring(0, 5);
        
        return !props.reservas.some(reserva => {
          if (idReserva && reserva.id_reserva === idReserva) return false;
          
          // Comparar fechas en formato ISO y horas normalizadas
          return reserva.fecha === fecha && 
                reserva.hora.substring(0, 5) === horaNormalizada && 
                reserva.id_mesa === idMesa;
        });
      };
  
      const rules = {
        required: (v: unknown) => !!v || 'Este campo es requerido',
        minValue: (min: number) => (v: number) => v >= min || `El valor debe ser mayor o igual a ${min}`,
        // Nueva regla para validar capacidad
        capacidadMesa: () => (v: number) => {
          const mesa = form.value.id_mesa;
          return !mesa || v <= mesa.capacidad 
            ? true 
            : `La mesa solo tiene capacidad para ${mesa.capacidad} personas`;
        },

        mesaDisponible: () => {
          return () => {
            if (!form.value.id_mesa || !form.value.fecha || !form.value.hora) return true;
            
            const idReserva = props.initialValue?.id_reserva;
            const idMesa = form.value.id_mesa.id_mesa;
            
            return isMesaDisponible(form.value.fecha, form.value.hora, idMesa, idReserva) || 
                  'Esta mesa ya está reservada para la fecha y hora seleccionadas';
          };
        }
      };
  
      const resetForm = () => {
        form.value = {
          fecha: new Date().toISOString().split('T')[0],
          hora: '20:00',
          numero_de_personas: 2,
          id_comensal: null,
          id_mesa: null
        };
      };
  
      watch(() => props.initialValue, (newValue) => {
        if (newValue) {
          form.value = {
            fecha: newValue.fecha,
            hora: newValue.hora,
            numero_de_personas: newValue.numero_de_personas,
            id_comensal: props.comensales.find(c => c.id_comensal === newValue.id_comensal) || null,
            id_mesa: props.mesas.find(m => m.id_mesa === newValue.id_mesa) || null
          };
        } else {
          resetForm();
        }
      }, { immediate: true });
  
      const submit = () => {
        if (!isFormValid.value) return;

        if (!form.value.id_mesa || !isMesaDisponible(
          form.value.fecha, 
          form.value.hora,
          form.value.id_mesa.id_mesa,
          props.initialValue?.id_reserva
        )) {
          return;
        }
  
        const reservaData: Partial<Reserva> = {
          fecha: form.value.fecha,
          hora: form.value.hora,
          numero_de_personas: Number(form.value.numero_de_personas),
          id_comensal: form.value.id_comensal?.id_comensal as number,
          id_mesa: form.value.id_mesa?.id_mesa as number
        };
  
        if (props.initialValue?.id_reserva) {
          reservaData.id_reserva = props.initialValue.id_reserva;
        }
  
        emit('submit', reservaData);
      };
  
      return {
        forceValidation,
        form,
        isEdit,
        isFormValid,
        rules,
        submit,
        resetForm,
        isMesaDisponible,
        checkMesaDisponible,
        mesaError,
      };
    }
  });
  </script>
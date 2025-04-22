import type { Reserva } from '../types';

export class ReservaRequest {
    fecha?: string;
    hora?: string;
    numero_de_personas?: number;
    id_comensal?: number;
    id_mesa?: number;
    
    constructor(data: Partial<Reserva>) {
      this.fecha = data.fecha;
      this.hora = this.formatTime(data.hora);
      this.numero_de_personas = data.numero_de_personas;
      this.id_comensal = data.id_comensal;
      this.id_mesa = data.id_mesa;
    }
    
    private formatTime(time?: string): string | undefined {
      if (!time) return undefined;
      return time.split(':').length === 2 ? `${time}:00` : time;
    }
    
    toJSON() {
      return {
        fecha: this.fecha,
        hora: this.hora,
        numero_de_personas: this.numero_de_personas,
        id_comensal: this.id_comensal,
        id_mesa: this.id_mesa
      };
    }
  }
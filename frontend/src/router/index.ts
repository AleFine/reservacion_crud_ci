import { createRouter, createWebHistory } from 'vue-router';
import ReservasManager from '../views/ReservasManager.vue';

const routes = [
  { path: '/', redirect: '/reservas' },
  { path: '/reservas', name: 'Reservas', component: ReservasManager }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

export default router;
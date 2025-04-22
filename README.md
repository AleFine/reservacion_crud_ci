# Sistema de Reservas de Restaurante

Un sistema básico de reservas para restaurantes con un CRUD para administrar comensales, mesas y reservas. Desarrollado con Vue.js (TypeScript) en el frontend y Laravel en el backend, con el backend contenido en docker.

## Descripción del Proyecto

Este proyecto implementa un sistema de reservas para restaurantes que permite:
- Gestionar información de comensales
- Gestionar información de las mesas
- Crear y gestionar reservas

La aplicación está estructurada en tres componentes principales:
- Frontend desarrollado en Vue.js, Vuetify y TypeScript
- Backend API RESTful desarrollado en Laravel (incluye test unitarios)
- Entorno de contenedores Docker para desarrollo y despliegue

Para documentación se utilizó Swagger y estándar PHPDoc

## Requisitos Previos

- [Docker](https://www.docker.com/get-started)  
- [Docker Compose](https://docs.docker.com/compose/install/)  
- [Git](https://git-scm.com/downloads)  
- [Node.js (incluye npm)](https://nodejs.org/) – Se recomienda instalar la versión **LTS**

> ⚠️ **Nota para usuarios de Windows**:  
> Se recomienda activar [WSL 2 (Windows Subsystem for Linux)](https://learn.microsoft.com/windows/wsl/install) y configurarlo como backend de Docker para un mejor rendimiento y compatibilidad.  
> Puedes instalar WSL directamente desde PowerShell ejecutando:
>
> ```powershell
> wsl --install
> 

## Instalación y Configuración

### 1. Clonar el repositorio

```bash
git clone https://github.com/AleFine/reservaciones_crud.git
```
```bash
cd reservaciones_crud
```
### 2. Iniciar los contenedores Docker

```bash
docker-compose up -d --build
```

Este comando iniciará los siguientes servicios (contenedores):
- Servidor web (Nginx) - Puerto 8080
- PHP-FPM para ejecutar Laravel
- MySQL - Puerto 3306
- phpMyAdmin - Puerto 8090 (en caso no se tuviese un administrador para verificar los registros de la base de datos)

> Aunque el archivo `docker-compose.yml` define **6 servicios**, solo **4 de ellos se ejecutan de forma persistente** al iniciar la aplicación. Los otros 2 (`composer` y `artisan`) se ejecutan solo cuando es necesario.

### 3. Configurar el Backend (Laravel)

```bash
# Entrar al contenedor de Composer para instalar dependencias
docker-compose run --rm composer install

# Copiar los archivos de variables de entorno

# .env (laravel)
copy .\backend\.env.example .\backend\.env

# .env (docker mysql)
copy .\docker\mysql\.env.example .\docker\mysql\.env

# Los .env ya están configurados con credenciales de muestra, 
# no es necesario editarlos a menos que se tenga 
# el puerto 3306 de Mysql ocupado (si fuera el caso, tambien
# edite el docker-compose.yml).

# Ejecutar migraciones
docker-compose run --rm artisan migrate

# (Opcional) Para poblar la base de datos con datos falsos.
docker-compose run --rm artisan db:seed

# (Opcional) Para ejecutar los test unitarios y test de características.
docker-compose run --rm artisan test

```
>Nota: Para ejecutar cualquier comando **artisan** o **composer** dentro del proyecto

>asegurese de hacerlo con `docker-compose run --rm artisan/composer...`

### 4. Configurar el Frontend (Vue.js)

```bash
# Navegar al directorio del frontend
cd frontend

# Instalar dependencias
npm install

# Iniciar servidor de desarrollo
npm run dev
```

## Acceso a la Aplicación

- **Frontend**: Acceda a la aplicación en [http://localhost:5173](http://localhost:5173) (por defecto)
- **Documentación de la API (Swagger)**: Detalles sobre los API endpoints disponibles en [http://localhost:8080/api/documentation](http://localhost:8080/api/documentation)
- **phpMyAdmin**: Gestión de base de datos en [http://localhost:8090](http://localhost:8090)

## Tecnologías Utilizadas

### Frontend
- Vue.js 3 con Composition API
- Vuetify como framework de componentes Vue
- TypeScript
- Vue Router para navegación
- Pinia para gestión de estado
- Vite como bundler y servidor de desarrollo
- Patrón de servicios para comunicación con API
- ESLint como linter para detección de errores

### Backend
- Laravel 12
- Eloquent ORM
- API Resources para transformación de respuestas
- Migraciones y Seeders para gestión de base de datos
- PHPUnit y Factories para asegurar las pruebas unitarias
- Documentación de la API RESTful con Swagger y comentarios con estandar PHPDoc

### Infraestructura
- Docker y Docker Compose
- Nginx como servidor web
- MySQL como base de datos
- PHP-FPM para procesamiento PHP

## Estructura del Proyecto

### Backend (Laravel)
```
backend/
├── app/
│   ├── Http/Controllers/ # Controladores para comensales, mesas y reservas
│   ├── Models/           # Modelos para comensales, mesas y reservas
│   └── Resources/        # Recursos API para transformación de datos
├── database/
│   ├── factories/       # Factories para crear datos falsos
│   ├── migrations/      # Migraciones para crear las tablas
│   └── seeders/         # Seeders para ejecutar los factories
├── tests/
│   ├── Feature/       # Gestión de Tests de características
│   └── Unit/         # Gestión de Tests Unitarios 
└── routes/
    └── api.php          # Definición de rutas API
```

### Frontend (Vue.js + TypeScript)
```
frontend/
├── components/
│   ├── comensales/      # Formulario (Modal) y Tabla para comensales.
│   ├── common/          # Componentes reutilizables (FilterPanel)   
│   ├── mesas/           # Formulario (Modal) y Tabla para mesas.
│   └── reservas/        # Formulario (Modal) y Tabla para reservas.
├── composables/
│   └── usePagination.ts  # Composable para manejar la paginación
├── requests/
│   └── reservaRequest.ts # Objetos de request para asegurar su formato 
├── router/
│   └── index.ts          # Configuración de rutas de Vue Router
├── services/
│   ├── api.ts             # Instancia de Axios para las peticiones HTTP
│   ├── comensalService.ts # Servicio para gestionar peticiones de comensales
│   ├── mesaService.ts     # Servicio para gestionar peticiones de mesas
│   └── reservaService.ts  # Servicio para gestionar peticiones de reservas
├── stores/
│   ├── comensalStore.ts # Store para estado de comensales (Pinia)
│   ├── mesaStore.ts     # Store para estado de mesas (Pinia)
│   └── reservaStore.ts  # Store para estado de reservas (Pinia)
├── types/
│   └── index.ts         # Definiciones de tipos TypeScript
├── views/
│   └── ReservasManager.vue # Vista principal del gestor de reservas
├── App.vue              # Componente raíz
├── main.ts              # Punto de entrada de la aplicación
└── style.css            # Estilos globales
```

### Docker
```
docker/
├── dockerfiles/         # Archivos Dockerfile
├── mysql/               # Almacenamiento de la database
└── nginx/               # Configuración de Nginx
```
---

## Estructura de Tablas
![DATABASE](https://drive.google.com/uc?export=view&id=1x4FCQfYnzlB2Fga4tlnmBBrJB03xLEPa)
---

### 1. Comensales (`comensales`)

Contiene los datos personales de los clientes que realizan reservas.

| Campo        | Tipo         | Migración (Laravel)                      | Descripción                           |
|--------------|--------------|------------------------------------------|----------------------------------------|
| `id_comensal`| BIGINT (PK)  | `$table->id('id_comensal');`            | Identificador único del comensal.     |
| `nombre`     | VARCHAR(255) | `$table->string('nombre');`             | Nombre completo del comensal.         |
| `correo`     | VARCHAR(255) | `$table->string('correo')->unique();`   | Correo electrónico.                   |
| `telefono`   | VARCHAR(255) | `$table->string('telefono')->nullable();`| Número de contacto.                  |
| `direccion`  | VARCHAR(255) | `$table->string('direccion')->nullable();`| Dirección del comensal.             |

---

### 2. Reservas (`reservas`)

Registra las reservas realizadas por los comensales.

| Campo                | Tipo         | Migración (Laravel)                                                                 | Descripción                                 |
|----------------------|--------------|--------------------------------------------------------------------------------------|----------------------------------------------|
| `id_reserva`         | BIGINT (PK)  | `$table->id('id_reserva');`                                                         | Identificador único de la reserva.          |
| `fecha`              | DATE         | `$table->date('fecha');`                                                             | Fecha de la reserva.                        |
| `hora`               | TIME         | `$table->time('hora');`                                                              | Hora de la reserva.                         |
| `numero_de_personas` | INT          | `$table->integer('numero_de_personas');`                                             | Número de personas que asistirán.           |
| `id_comensal`        | BIGINT (FK)  | `$table->foreignId('id_comensal')->references('id_comensal')->on('comensales')->onDelete('RESTRICT');` | ID del comensal que realiza la reserva.     |
| `id_mesa`            | BIGINT (FK)  | `$table->foreignId('id_mesa')->references('id_mesa')->on('mesas')->onDelete('RESTRICT');`              | ID de la mesa reservada.                    |

---

### 3. Mesas (`mesas`)

Define las mesas disponibles en el restaurante.

| Campo         | Tipo         | Migración (Laravel)                                 | Descripción                                |
|---------------|--------------|-----------------------------------------------------|---------------------------------------------|
| `id_mesa`     | BIGINT (PK)  | `$table->id('id_mesa');`                           | Identificador único de la mesa.            |
| `numero_mesa` | VARCHAR(255) | `$table->string('numero_mesa')->unique();`         | Código o número identificador de la mesa.  |
| `capacidad`   | INT          | `$table->integer('capacidad');`                    | Número máximo de personas que soporta.     |
| `ubicacion`   | VARCHAR(255) | `$table->string('ubicacion')->nullable();`         | Ubicación dentro del restaurante.          |

---

## Relaciones entre Tablas

- Un **comensal** puede realizar múltiples **reservas** (Relación 1:N).
- Una **mesa** puede estar asociada a múltiples **reservas** (Relación 1:N).

## Comandos Útiles

### Comandos Docker

```bash
# Ver los contenedores en ejecución
docker-compose ps

# Detener todos los contenedores
docker-compose down

# Reiniciar todos los contenedores
docker-compose restart

# Ver logs de los contenedores
docker-compose logs -f
```

### Comandos Laravel (a través de Artisan)

```bash
# Ejecutar migraciones
docker-compose run --rm artisan migrate

# Revertir migraciones
docker-compose run --rm artisan migrate:rollback

# Recrear la base de datos
docker-compose run --rm artisan migrate:fresh --seed

# Ejecutar pruebas unitarias
docker-compose run --rm artisan test

# Limpiar caché
docker-compose run --rm artisan cache:clear
```

### Comandos Vue.js

```bash

# Verificar tipos TypeScript
npm run type-check

# Ejecutar linter
npm run lint

```

## Flujo de Trabajo de Desarrollo

### Frontend
1. Los componentes se organizan por entidad (comensales, mesas, reservas)
2. Los servicios manejan la comunicación con la API backend
3. Los stores (Pinia) gestionan el estado global de la aplicación
4. El composable `usePagination` facilita la implementación de paginación en listados

### Backend
1. Los controladores gestionan las peticiones HTTP
2. Los modelos Eloquent definen la estructura de datos y relaciones
3. Los Resources transforman los datos para las respuestas API
4. Las migraciones definen la estructura de la base de datos

## Solución de Problemas Comunes

### Problemas de permisos

Si encuentra problemas de permisos en Laravel:

```bash
# Ajustar permisos en el directorio del backend
docker-compose run --rm php chmod -R 777 storage bootstrap/cache
```

### Problemas de conexión a la base de datos

Verifique que:
1. El servicio MySQL esté funcionando (`docker-compose ps`)
2. Las credenciales en el archivo `.env` coincidan con las del `docker-compose.yml`
3. La base de datos haya sido creada correctamente

### Problemas con el frontend

Si tiene problemas con el frontend:

```bash
# Limpiar caché de npm
npm cache clean --force

# Reinstalar dependencias
rm -rf node_modules
npm install
```

### Problemas con TypeScript

Si encuentra errores de tipo en TypeScript:

```bash
# Verificar errores de tipo
npm run type-check
```

## 📞 Contacto

- 🔗 [LinkedIn](www.linkedin.com/in/kevin-e-parimango-gómez-832315174)  
- 📱 +51 929686486  
- ✉️ keving.kpg@gmail.com

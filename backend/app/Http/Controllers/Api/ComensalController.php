<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComensalResource;
use App\Models\Comensal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Requests\StoreComensalRequest;
use App\Http\Requests\UpdateComensalRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Exception;

class ComensalController extends Controller
{
    /**
     * Obtiene una lista paginada de comensales con opción de búsqueda
     * 
     * @param Request $request Solicitud HTTP
     * @return \Illuminate\Http\JsonResponse|ComensalResource
     * 
     * @throws \Exception Error genérico (500)
     * 
     * @queryParam per_page integer Cantidad de elementos por página. Ejemplo: 15
     * @queryParam searchTerm string Término para buscar en nombre, correo o teléfono. Ejemplo: "Juan"
     */
    public function index(Request $request)
    {
        try {
            // paginación
            $perPage = $request->input('per_page', 15); 
            $query = Comensal::query();

            // filtro genérico por un término de búsqueda
            $query->when($request->filled('searchTerm'), function ($q) use ($request) {
                return $q->where('nombre', 'LIKE', '%' . $request->searchTerm . '%')
                         ->orWhere('correo', 'LIKE', '%' . $request->searchTerm . '%')
                         ->orWhere('telefono', 'LIKE', '%' . $request->searchTerm . '%');
            });

            $comensales = $query->paginate($perPage)->appends($request->query());
            
            return ComensalResource::collection($comensales);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la lista de comensales',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crea un nuevo comensal en el sistema
     * 
     * @param Request $request Solicitud HTTP con datos del comensal
     * @return \Illuminate\Http\JsonResponse|ComensalResource
     * 
     * @throws ValidationException Validación fallida (422)
     * @throws QueryException Error de base de datos (500 o 409)
     * @throws \Exception Error genérico (500)
     * 
     * @bodyParam nombre string required Nombre del comensal. Ejemplo: "Juan Pérez"
     * @bodyParam correo string required Email único. Ejemplo: "juan@example.com"
     * @bodyParam telefono string nullable Teléfono. Ejemplo: "+5491123456789"
     * @bodyParam direccion string nullable Dirección. Ejemplo: "Calle Falsa 123"
     */
    public function store(StoreComensalRequest $request)
    {
        try {
            $validated = $request->validated();
            $comensal = Comensal::create($validated);

            return (new ComensalResource($comensal))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
                
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (QueryException $e) {
            // captura de errores de base de datos
            $errorCode = $e->errorInfo[1] ?? null;
            
            if ($errorCode == 1062) { // este es el código de mysql para duplicado
                return response()->json([
                    'message' => 'El correo electrónico ya está registrado'
                ], Response::HTTP_CONFLICT);
            }
            
            return response()->json([
                'message' => 'Error al crear el comensal',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al crear el comensal',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Muestra los detalles de un comensal específico
     * 
     * @param int $id ID único del comensal
     * @return \Illuminate\Http\JsonResponse|ComensalResource
     * 
     * @throws ModelNotFoundException Comensal no encontrado (404)
     * @throws \Exception Error genérico (500)
     */
    public function show($id)
    {
        try {
            $comensal = Comensal::findOrFail($id);

            return new ComensalResource($comensal);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Comensal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el comensal',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualiza los datos de un comensal existente
     * 
     * @param Request $request Solicitud HTTP con datos a actualizar
     * @param int $id ID único del comensal a actualizar
     * @return \Illuminate\Http\JsonResponse|ComensalResource
     * 
     * @throws ModelNotFoundException Comensal no encontrado (404)
     * @throws ValidationException Validación fallida (422)
     * @throws QueryException Error de base de datos (500 o 409)
     * @throws \Exception Error genérico (500)
     * 
     * @bodyParam nombre string Nombre del comensal. Ejemplo: "Juan Pérez Actualizado"
     * @bodyParam correo string Email único (ignorando el actual). Ejemplo: "nuevo@email.com"
     * @bodyParam telefono string Teléfono. Ejemplo: "+5491187654321"
     * @bodyParam direccion string Dirección. Ejemplo: "Nueva Dirección 456"
     */
    public function update(UpdateComensalRequest $request, $id)
    {
        try {
            $comensal = Comensal::findOrFail($id);
            $validated = $request->validated();
            $comensal->update($validated);
            
            return new ComensalResource($comensal);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Comensal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (QueryException $e) {
            // captura de errores de base de datos
            $errorCode = $e->errorInfo[1] ?? null;
            
            if ($errorCode == 1062) { // este es el código de mysql para duplicado
                return response()->json([
                    'message' => 'El correo electrónico ya está registrado por otro comensal'
                ], Response::HTTP_CONFLICT);
            }
            
            return response()->json([
                'message' => 'Error al actualizar el comensal',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el comensal',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Elimina un comensal del sistema
     * 
     * @param int $id ID único del comensal a eliminar
     * @return \Illuminate\Http\JsonResponse
     * 
     * @throws ModelNotFoundException Comensal no encontrado (404)
     * @throws \Exception Error genérico (500) o conflicto con reservas (409)
     */
    public function destroy($id)
    {
        try {
            $comensal = Comensal::findOrFail($id);
            
            if($comensal->reservas()->exists()) {
                return response()->json([
                    'message' => 'No se puede eliminar el comensal porque tiene reservas asociadas'
                ], Response::HTTP_CONFLICT);
            }
            
            $comensal->delete();
            
            return response()->json([
                'message' => 'Comensal eliminado correctamente'
            ], Response::HTTP_OK);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Comensal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el comensal',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
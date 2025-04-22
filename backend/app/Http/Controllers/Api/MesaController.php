<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MesaResource;
use App\Models\Mesa;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMesaRequest;
use App\Http\Requests\UpdateMesaRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Exception;

class MesaController extends Controller
{

    /**
     * Obtiene una lista paginada de mesas con búsqueda
     * 
     * @param Request $request Solicitud HTTP
     * @return \Illuminate\Http\JsonResponse|MesaResource
     * 
     * @throws \Exception Error genérico (500)
     * 
     * @queryParam per_page integer Cantidad de elementos por página. Ejemplo: 15
     * @queryParam searchTerm string Buscar en número de mesa o ubicación. Ejemplo: "terraza"
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $query = Mesa::query();

            // filtro para búsqueda por número de mesa o ubicación
            $query->when($request->filled('searchTerm'), function ($q) use ($request) {
                $term = $request->searchTerm;
                return $q->where('numero_mesa', 'LIKE', "%{$term}%")
                         ->orWhere('ubicacion', 'LIKE', "%{$term}%");
            });

            $mesas = $query->paginate($perPage)->appends($request->query());
            
            return MesaResource::collection($mesas);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la lista de mesas',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crea una nueva mesa en el sistema
     * 
     * @param Request $request Solicitud HTTP con datos de la mesa
     * @return \Illuminate\Http\JsonResponse|MesaResource
     * 
     * @throws ValidationException Validación fallida (422)
     * @throws QueryException Error de base de datos (409 o 500)
     * @throws \Exception Error genérico (500)
     * 
     * @bodyParam numero_mesa string required Número único de mesa. Ejemplo: "MESA-01"
     * @bodyParam capacidad integer required Capacidad mínima 1. Ejemplo: 4
     * @bodyParam ubicacion string nullable Ubicación de la mesa. Ejemplo: "Terraza"
     */
    public function store(StoreMesaRequest $request)
    {
        try {
            $validated = $request->validated();
            $mesa = Mesa::create($validated);
            
            return (new MesaResource($mesa))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
                
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? null;
            
            if ($errorCode == 1062) {
                return response()->json([
                    'message' => 'El número de mesa ya está registrado'
                ], Response::HTTP_CONFLICT);
            }
            
            return response()->json([
                'message' => 'Error al crear la mesa',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al crear la mesa',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Muestra los detalles de una mesa específica
     * 
     * @param int $id ID único de la mesa
     * @return \Illuminate\Http\JsonResponse|MesaResource
     * 
     * @throws ModelNotFoundException Mesa no encontrada (404)
     * @throws \Exception Error genérico (500)
     */
    public function show($id)
    {
        try {
            $mesa = Mesa::findOrFail($id);
            return new MesaResource($mesa);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Mesa no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la mesa',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualiza los datos de una mesa existente
     * 
     * @param Request $request Solicitud HTTP con datos a actualizar
     * @param int $id ID único de la mesa a actualizar
     * @return \Illuminate\Http\JsonResponse|MesaResource
     * 
     * @throws ModelNotFoundException Mesa no encontrada (404)
     * @throws ValidationException Validación fallida (422)
     * @throws QueryException Error de base de datos (409 o 500)
     * @throws \Exception Error genérico (500)
     * 
     * @bodyParam numero_mesa string Número único de mesa (ignorando actual). Ejemplo: "MESA-02"
     * @bodyParam capacidad integer Capacidad mínima 1. Ejemplo: 6
     * @bodyParam ubicacion string Ubicación de la mesa. Ejemplo: "Interior"
     */
    public function update(UpdateMesaRequest $request, $id)
    {
        try {
            $mesa = Mesa::findOrFail($id);
            $validated = $request->validated();
            $mesa->update($validated);
            
            return new MesaResource($mesa);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Mesa no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? null;
            
            if ($errorCode == 1062) {
                return response()->json([
                    'message' => 'El número de mesa ya está en uso'
                ], Response::HTTP_CONFLICT);
            }
            
            return response()->json([
                'message' => 'Error al actualizar la mesa',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la mesa',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Elimina una mesa del sistema
     * 
     * @param int $id ID único de la mesa a eliminar
     * @return \Illuminate\Http\JsonResponse
     * 
     * @throws ModelNotFoundException Mesa no encontrada (404)
     * @throws QueryException Error de integridad por reservas (409)
     * @throws \Exception Error genérico (500)
     */
    public function destroy($id)
    {
        try {
            $mesa = Mesa::findOrFail($id);
            
            // verifica si tiene reservas primero
            if ($mesa->reservas->count() > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar la mesa porque tiene reservas asociadas'
                ], Response::HTTP_CONFLICT);
            }
            
            $mesa->delete();
            
            return response()->json([
                'message' => 'Mesa eliminada correctamente'
            ], Response::HTTP_OK);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Mesa no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (QueryException $e) {
            // si la verificación anterior falla, aseguramos la restricción con el código de mysql
            if ($e->errorInfo[1] == 1451) {  // código mysql para restricción de clave externa
                return response()->json([
                    'message' => 'No se puede eliminar la mesa porque tiene reservas asociadas'
                ], Response::HTTP_CONFLICT);
            }
            
            return response()->json([
                'message' => 'Error al eliminar la mesa',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la mesa',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
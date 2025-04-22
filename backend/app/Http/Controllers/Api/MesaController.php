<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MesaResource;
use App\Models\Mesa;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * @OA\Tag(
 *     name="Mesas",
 *     description="Gestión de mesas del restaurante"
 * )
 */
class MesaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/mesas",
     *     tags={"Mesas"},
     *     summary="Listar mesas paginadas",
     *     operationId="getMesas",
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="searchTerm",
     *         in="query",
     *         description="Buscar por número o ubicación",
     *         required=false,
     *         @OA\Schema(type="string", example="Terraza")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de mesas",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/MesaResource")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/mesas",
     *     tags={"Mesas"},
     *     summary="Crear nueva mesa",
     *     operationId="createMesa",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/MesaRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mesa creada",
     *         @OA\JsonContent(ref="#/components/schemas/MesaResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación fallida",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto - Número de mesa duplicado",
     *         @OA\JsonContent(ref="#/components/schemas/ConflictResponse")
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'numero_mesa' => 'required|string|unique:mesas,numero_mesa',
                'capacidad' => 'required|integer|min:1',
                'ubicacion' => 'nullable|string|max:255'
            ]);

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
     * @OA\Get(
     *     path="/api/mesas/{id}",
     *     tags={"Mesas"},
     *     summary="Obtener mesa específica",
     *     operationId="getMesa",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la mesa",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la mesa",
     *         @OA\JsonContent(ref="#/components/schemas/MesaResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/mesas/{id}",
     *     tags={"Mesas"},
     *     summary="Actualizar mesa existente",
     *     operationId="updateMesa",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la mesa",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/MesaRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mesa actualizada",
     *         @OA\JsonContent(ref="#/components/schemas/MesaResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto - Número de mesa duplicado",
     *         @OA\JsonContent(ref="#/components/schemas/ConflictResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación fallida",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $mesa = Mesa::findOrFail($id);
            
            $validated = $request->validate([
                'numero_mesa' => 'sometimes|string|unique:mesas,numero_mesa,' . $mesa->id_mesa . ',id_mesa',
                'capacidad' => 'sometimes|integer|min:1',
                'ubicacion' => 'nullable|string|max:255'
            ]);

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
     * @OA\Delete(
     *     path="/api/mesas/{id}",
     *     tags={"Mesas"},
     *     summary="Eliminar mesa",
     *     operationId="deleteMesa",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la mesa",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mesa eliminada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Mesa eliminada correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto - Tiene reservas asociadas",
     *         @OA\JsonContent(ref="#/components/schemas/ConflictResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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


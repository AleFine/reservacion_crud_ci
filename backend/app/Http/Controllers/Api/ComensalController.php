<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComensalResource;
use App\Models\Comensal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * @OA\Tag(
 *     name="Comensales",
 *     description="Gestión de comensales del restaurante"
 * )
 */
class ComensalController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/comensales",
     *     tags={"Comensales"},
     *     summary="Listar comensales paginados",
     *     operationId="getComensales",
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
     *         description="Buscar por nombre, correo o teléfono",
     *         required=false,
     *         @OA\Schema(type="string", example="Juan")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de comensales",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ComensalResource")),
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
            // paginación
            $perPage = $request->input('per_page', 15); 
            $query = Comensal::query();

            // filtro genérico por término de búsqueda
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
     * @OA\Post(
     *     path="/api/comensales",
     *     tags={"Comensales"},
     *     summary="Crear nuevo comensal",
     *     operationId="createComensal",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ComensalRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comensal creado",
     *         @OA\JsonContent(ref="#/components/schemas/ComensalResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación fallida",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto - Correo duplicado",
     *         @OA\JsonContent(ref="#/components/schemas/ConflictResponse")
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'correo' => 'required|email|unique:comensales,correo',
                'telefono' => 'nullable|string|max:20',
                'direccion' => 'nullable|string|max:255'
            ]);

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
     * @OA\Get(
     *     path="/api/comensales/{id}",
     *     tags={"Comensales"},
     *     summary="Obtener comensal específico",
     *     operationId="getComensal",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del comensal",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del comensal",
     *         @OA\JsonContent(ref="#/components/schemas/ComensalResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No encontrado",
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
     * @OA\Put(
     *     path="/api/comensales/{id}",
     *     tags={"Comensales"},
     *     summary="Actualizar comensal existente",
     *     operationId="updateComensal",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del comensal",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ComensalRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comensal actualizado",
     *         @OA\JsonContent(ref="#/components/schemas/ComensalResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto - Correo duplicado",
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
            $comensal = Comensal::findOrFail($id);
            
            $validated = $request->validate([
                'nombre' => 'sometimes|string|max:255',
                'correo' => 'sometimes|email|unique:comensales,correo,' . $comensal->id_comensal . ',id_comensal',
                'telefono' => 'nullable|string|max:20',
                'direccion' => 'nullable|string|max:255'
            ]);

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
     * @OA\Delete(
     *     path="/api/comensales/{id}",
     *     tags={"Comensales"},
     *     summary="Eliminar comensal",
     *     operationId="deleteComensal",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del comensal",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comensal eliminado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Comensal eliminado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No encontrado",
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

/**
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="from",         type="integer", example=1),
 *     @OA\Property(property="last_page",    type="integer", example=5),
 *     @OA\Property(property="path",         type="string",  example="http://localhost/api/comensales"),
 *     @OA\Property(property="per_page",     type="integer", example=15),
 *     @OA\Property(property="to",           type="integer", example=15),
 *     @OA\Property(property="total",        type="integer", example=75)
 * )
 * 
 * @OA\Schema(
 *     schema="NotFoundResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Recurso no encontrado")
 * )
 *
 * @OA\Schema(
 *     schema="ConflictResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Conflicto detectado")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationLinks",
 *     type="object",
 *     @OA\Property(property="first", type="string", example="http://localhost/api/comensales?page=1"),
 *     @OA\Property(property="last", type="string", example="http://localhost/api/comensales?page=5"),
 *     @OA\Property(property="prev", type="string", example="http://localhost/api/comensales?page=1"),
 *     @OA\Property(property="next", type="string", example="http://localhost/api/comensales?page=3")
 * )
 *
 *
 * @OA\Schema(
 *     schema="ComensalResource",
 *     type="object",
 *     @OA\Property(property="id_comensal", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Juan Pérez"),
 *     @OA\Property(property="correo", type="string", example="juan@example.com"),
 *     @OA\Property(property="telefono", type="string", example="+5491123456789"),
 *     @OA\Property(property="direccion", type="string", example="Calle Falsa 123")
 * )
 *
 * @OA\Schema(
 *     schema="ComensalRequest",
 *     type="object",
 *     required={"nombre", "correo"},
 *     @OA\Property(property="nombre", type="string", example="Juan Pérez"),
 *     @OA\Property(property="correo", type="string", example="juan@example.com"),
 *     @OA\Property(property="telefono", type="string", nullable=true, example="+5491123456789"),
 *     @OA\Property(property="direccion", type="string", nullable=true, example="Calle Falsa 123")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Error interno del servidor"),
 *     @OA\Property(property="error", type="string", example="Detalle del error")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Error de validación"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties=@OA\Property(
 *             type="array",
 *             @OA\Items(type="string", example="El campo es requerido")
 *         )
 *     )
 * )
 *
 * 
 * @OA\Schema(
 *     schema="ReservaRequest",
 *     type="object",
 *     required={"fecha", "hora", "numero_de_personas", "id_comensal", "id_mesa"},
 *     @OA\Property(
 *         property="fecha", 
 *         type="string", 
 *         format="date", 
 *         example="2024-03-15"
 *     ),
 *     @OA\Property(
 *         property="hora", 
 *         type="string", 
 *         format="time", 
 *         example="20:00"
 *     ),
 *     @OA\Property(
 *         property="numero_de_personas", 
 *         type="integer", 
 *         minimum=1,
 *         example=4
 *     ),
 *     @OA\Property(
 *         property="id_comensal", 
 *         type="integer", 
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="id_mesa", 
 *         type="integer", 
 *         example=5
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ReservaResource",
 *     type="object",
 *     @OA\Property(property="id_reserva", type="integer", example=1),
 *     @OA\Property(property="fecha", type="string", format="date", example="2024-03-15"),
 *     @OA\Property(property="hora", type="string", format="time", example="20:00"),
 *     @OA\Property(property="numero_de_personas", type="integer", example=4),
 *     @OA\Property(
 *         property="comensal", 
 *         ref="#/components/schemas/ComensalResource"
 *     ),
 *     @OA\Property(
 *         property="mesa", 
 *         ref="#/components/schemas/MesaResource"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="MesaRequest",
 *     type="object",
 *     required={"numero_mesa", "capacidad"},
 *     @OA\Property(
 *         property="numero_mesa", 
 *         type="string", 
 *         example="MESA-01"
 *     ),
 *     @OA\Property(
 *         property="capacidad", 
 *         type="integer", 
 *         minimum=1,
 *         example=4
 *     ),
 *     @OA\Property(
 *         property="ubicacion", 
 *         type="string", 
 *         nullable=true,
 *         example="Terraza"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="MesaResource",
 *     type="object",
 *     @OA\Property(property="id_mesa", type="integer", example=1),
 *     @OA\Property(property="numero_mesa", type="string", example="MESA-01"),
 *     @OA\Property(property="capacidad", type="integer", example=4),
 *     @OA\Property(property="ubicacion", type="string", example="Terraza")
 * )
 * 
 */
class SwaggerSchemasDummy {}
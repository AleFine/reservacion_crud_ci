<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservaResource;
use App\Models\Reserva;
use App\Models\Comensal;
use App\Models\Mesa;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Reservas",
 *     description="Gestión de reservas del restaurante"
 * )
 */
class ReservaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reservas",
     *     tags={"Reservas"},
     *     summary="Listar reservas paginadas",
     *     operationId="getReservas",
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Filtrar por fecha (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-03-15")
     *     ),
     *     @OA\Parameter(
     *         name="searchTerm",
     *         in="query",
     *         description="Buscar por comensal, mesa o número de personas",
     *         required=false,
     *         @OA\Schema(type="string", example="Juan")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de reservas",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ReservaResource")),
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
            $query = Reserva::with(['comensal', 'mesa']);

            // Filtro por fecha enviado como 'date'
            $query->when($request->filled('date'), function ($q) use ($request) {
                return $q->whereDate('fecha', $request->date);
            });

            // Filtro genérico por término de búsqueda: comensal, mesa o número personas
            $query->when($request->filled('searchTerm'), function ($q) use ($request) {
                $term = $request->searchTerm;
                return $q->where(function($sub) use ($term) {
                    $sub->whereHas('comensal', function ($q2) use ($term) {
                            $q2->where('nombre', 'LIKE', "%{$term}%")
                                ->orWhere('telefono', 'LIKE', "%{$term}%")
                                ->orWhere('correo', 'LIKE', "%{$term}%");
                        })
                        ->orWhereHas('mesa', function ($q3) use ($term) {
                            $q3->where('numero_mesa', 'LIKE', "%{$term}%");
                        });
                    if (is_numeric($term)) {
                        $sub->orWhere('numero_de_personas', '>=', (int)$term);
                    }
                });
            });

            $reservas = $query->paginate($perPage)->appends($request->query());
            
            return ReservaResource::collection($reservas);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la lista de reservas',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/reservas",
     *     tags={"Reservas"},
     *     summary="Crear nueva reserva",
     *     operationId="createReserva",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ReservaRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reserva creada",
     *         @OA\JsonContent(ref="#/components/schemas/ReservaResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación fallida",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto - Mesa ocupada",
     *         @OA\JsonContent(ref="#/components/schemas/ConflictResponse")
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'fecha' => 'required|date|after_or_equal:today',
                'hora' => 'required|date_format:H:i',
                'numero_de_personas' => 'required|integer|min:1',
                'id_comensal' => 'required|exists:comensales,id_comensal',
                'id_mesa' => 'required|exists:mesas,id_mesa'
            ]);

            // validación de capacidad
            $mesa = Mesa::findOrFail($validated['id_mesa']);
            if($mesa->capacidad < $validated['numero_de_personas']) {
                return response()->json([
                    'message' => 'La capacidad de la mesa es insuficiente'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // validación de reserva existente
            $existingReservation = Reserva::where('id_mesa', $validated['id_mesa'])
                ->whereDate('fecha', $validated['fecha'])
                ->where('hora', '=', $validated['hora'])
                ->exists();

            if($existingReservation) {
                return response()->json([
                    'message' => 'La mesa ya está reservada en esta fecha y hora'
                ], Response::HTTP_CONFLICT);
            }

            $reserva = Reserva::create($validated);
            
            return (new ReservaResource($reserva->load(['comensal', 'mesa'])))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
                
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? null;
            
            return response()->json([
                'message' => 'Error al crear la reserva',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al crear la reserva',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/reservas/{id}",
     *     tags={"Reservas"},
     *     summary="Obtener reserva específica",
     *     operationId="getReserva",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la reserva",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la reserva",
     *         @OA\JsonContent(ref="#/components/schemas/ReservaResource")
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
            $reserva = Reserva::with(['comensal', 'mesa'])->findOrFail($id);
            return new ReservaResource($reserva);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Reserva no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la reserva',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/reservas/{id}",
     *     tags={"Reservas"},
     *     summary="Actualizar reserva existente",
     *     operationId="updateReserva",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la reserva",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ReservaRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva actualizada",
     *         @OA\JsonContent(ref="#/components/schemas/ReservaResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto - Mesa ocupada",
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
            $reserva = Reserva::findOrFail($id);
            
            $validated = $request->validate([
                'fecha' => 'sometimes|date|after_or_equal:today',
                'hora' => 'sometimes|date_format:H:i:s',
                'numero_de_personas' => 'sometimes|integer|min:1',
                'id_comensal' => 'sometimes|exists:comensales,id_comensal',
                'id_mesa' => 'sometimes|exists:mesas,id_mesa'
            ]);

            // validación para la capacidad si se actualiza la mesa
            // o el número de personas
            if(isset($validated['id_mesa'])) {
                $mesa = Mesa::findOrFail($validated['id_mesa']);
                $capacidadRequerida = $validated['numero_de_personas'] ?? $reserva->numero_de_personas;
                
                if($mesa->capacidad < $capacidadRequerida) {
                    return response()->json([
                        'message' => 'La capacidad de la mesa es insuficiente'
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            // validación en caso se cambiara fecha/hora/mesa
            if(isset($validated['fecha']) || isset($validated['hora']) || isset($validated['id_mesa'])) {
                $fecha = $validated['fecha'] ?? $reserva->fecha;
                $hora = $validated['hora'] ?? $reserva->hora;
                $mesaId = $validated['id_mesa'] ?? $reserva->id_mesa;

                $existingReservation = Reserva::where('id_mesa', $mesaId)
                    ->whereDate('fecha', $fecha)
                    ->whereTime('hora', '=', $hora)
                    ->where('id_reserva', '!=', $id)
                    ->exists();

                if($existingReservation) {
                    return response()->json([
                        'message' => 'La mesa ya está reservada en esta fecha y hora'
                    ], Response::HTTP_CONFLICT);
                }
            }

            $reserva->update($validated);
            
            return new ReservaResource($reserva->load(['comensal', 'mesa']));
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Reserva no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Error al actualizar la reserva',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la reserva',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/reservas/{id}",
     *     tags={"Reservas"},
     *     summary="Eliminar reserva",
     *     operationId="deleteReserva",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la reserva",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva eliminada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Reserva eliminada correctamente")
     *         )
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
    public function destroy($id)
    {
        try {
            $reserva = Reserva::findOrFail($id);
            $reserva->delete();
            
            return response()->json([
                'message' => 'Reserva eliminada correctamente'
            ], Response::HTTP_OK);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Reserva no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la reserva',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

/**

 *
 * 
 */
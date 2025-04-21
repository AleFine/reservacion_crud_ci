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
use App\Http\Requests\StoreReservaRequest;
use App\Http\Requests\UpdateReservaRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;

class ReservaController extends Controller
{

    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $query = Reserva::with(['comensal', 'mesa']);

            // filtro por fecha enviado como 'date'
            $query->when($request->filled('date'), function ($q) use ($request) {
                return $q->whereDate('fecha', $request->date);
            });

            // filtro genérico por término de búsqueda: comensal, mesa o número personas
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

    public function store(StoreReservaRequest $request)
    {
        try {
            $validated = $request->validated();

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

    public function update(UpdateReservaRequest $request, $id)
    {
        try {
            $reserva = Reserva::findOrFail($id);
            $validated = $request->validated();

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
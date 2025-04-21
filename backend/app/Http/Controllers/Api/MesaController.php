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
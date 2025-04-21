<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mesa extends Model
{
    use HasFactory;

    protected $table = 'mesas';
    protected $primaryKey = 'id_mesa';
    public $timestamps = false; // para simplicidad uso false para evitar la creación de columnas created_at y updated_at

    protected $fillable = ['numero_mesa', 'capacidad', 'ubicacion'];

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'id_mesa');
    }
}

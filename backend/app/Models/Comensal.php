<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comensal extends Model
{
    use HasFactory;

    protected $table = 'comensales';
    protected $primaryKey = 'id_comensal';
    public $timestamps = false; // para simplicidad uso false para evitar la creación de columnas created_at y updated_at

    protected $fillable = ['nombre', 'correo', 'telefono', 'direccion'];

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'id_comensal');
    }
}

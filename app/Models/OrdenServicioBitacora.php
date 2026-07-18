<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenServicioBitacora extends Model
{
    use HasFactory;

    protected $table = 'orden_servicio_bitacora';

    protected $fillable = [
        'orden_servicio_id', 'user_id',
        'descripcion', 'horas_trabajadas', 'observaciones',
    ];

    protected $casts = [
        'horas_trabajadas' => 'decimal:2',
    ];

    public function orden()  { return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id'); }
    public function tecnico(){ return $this->belongsTo(User::class, 'user_id'); }
    public function fotos()  { return $this->hasMany(OrdenServicioBitacoraFoto::class, 'bitacora_id'); }
}

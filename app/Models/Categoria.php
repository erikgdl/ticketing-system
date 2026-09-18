<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
    ];

    public function chamados(): HasMany
    {
        return $this->hasMany(Chamado::class);
    }
}

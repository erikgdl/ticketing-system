<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChamadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string'],
            'prioridade' => ['required', 'string', 'in:baixa,media,alta,urgente'],
            'usuario_id' => ['required', 'exists:users,id'],
            'categoria_id' => ['required', 'exists:categorias,id'],
        ];
    }
}

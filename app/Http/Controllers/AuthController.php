<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'portal' => ['required', 'in:solicitante,equipe'],
        ]);

        $usuario = User::where('email', $dados['email'])->first();

        if (! $usuario || ! Hash::check($dados['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha inválidos.',
            ]);
        }

        $portalCorreto = $dados['portal'] === 'solicitante'
            ? $usuario->tipo === 'solicitante'
            : in_array($usuario->tipo, ['tecnico', 'admin'], true);

        if (! $portalCorreto) {
            throw ValidationException::withMessages([
                'email' => $dados['portal'] === 'solicitante'
                    ? 'Use o acesso da equipe para entrar com esta conta.'
                    : 'Esta conta deve entrar pelo acesso do solicitante.',
            ]);
        }

        $usuario->tokens()->delete();

        return response()->json([
            'token' => $usuario->createToken('helpdesk-web')->plainTextToken,
            'user' => $usuario,
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sessão encerrada.']);
    }
}

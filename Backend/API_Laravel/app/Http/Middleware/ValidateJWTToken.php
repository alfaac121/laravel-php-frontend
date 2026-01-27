<?php

namespace App\Http\Middleware;

use App\Models\Cuenta;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ValidateJWTToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Verificar y decodificar el token JWT
            $cuenta = JWTAuth::parseToken()->authenticate();

            // Verificar que el usuario exista
            if (!$cuenta instanceof Cuenta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cuenta no encontrada'
                ], 404);
            }

            $usuario = $cuenta->usuario;

            // Verificar que el usuario esté activo
            // Estado_id: 1 = Activo, 2 = Invisible, 3 = Eliminado
            if ($usuario->estado_id === 3) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Usuario eliminado'
                ], 403);
            }

            $payload = JWTAuth::getPayload();
            $jti = $payload->get('jti');

            $tokenActivo = DB::table('tokens_de_sesion')
                ->where('jti', $jti)
                ->where('cuenta_id', $cuenta->id)
                ->first();
            
            if (!$tokenActivo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sesión terminada. Inicie sesión nuevamente',
                    'session_replaced' => true
                ], 401);
            }

            // OK - Permitir que continúe la petición
            return $next($request);

        } catch (TokenExpiredException $e) {
            // El token expiró
            Log::error('Token expirado: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Token expirado. Por favor inicie sesión nuevamente'
            ], 401);

        } catch (TokenInvalidException $e) {
            // El token es inválido
            Log::error('Token inválido: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Token inválido',
            ], 401);

        } catch (JWTException $e) {
            // Token no proporcionado o error general
            Log::error('JWT Exception: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Token no proporcionado o inválido',
            ], 401);
        }
    }
}
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user and create token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            // Check if user exists
            $user = User::where('email', $request->email)->first();
            
            // Check if user exists and password is correct
            // Using a generic error message for security purposes
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Error de autenticación',
                    'errors' => [
                        'general' => ['Credenciales incorrectos']
                    ]
                ], 401);
            }

            // Check if user account is active (assuming there's an 'active' field)
            // Uncomment if you have an active status field
            /*
            if (!$user->active) {
                return response()->json([
                    'message' => 'Authentication failed',
                    'errors' => [
                        'account' => ['Su cuenta está desactivada. Por favor contacte al administrador.']
                    ]
                ], 403);
            }
            */

            // Authentication successful
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Inicio de sesión exitoso',
                'user' => $user,
                'token' => $token,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            Log::error('Database error during login: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error de conexión',
                'errors' => [
                    'database' => ['Error al conectar con la base de datos. Por favor intente nuevamente más tarde.']
                ]
            ], 503);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            
            // Check if it's a timeout error
            if (str_contains($e->getMessage(), 'timeout') || $e->getCode() == 504) {
                return response()->json([
                    'message' => 'Error de tiempo de espera',
                    'errors' => [
                        'timeout' => ['La solicitud ha excedido el tiempo de espera. Por favor intente nuevamente.']
                    ]
                ], 504);
            }
            
            return response()->json([
                'message' => 'Error de inicio de sesión',
                'errors' => [
                    'general' => ['Ha ocurrido un error durante el inicio de sesión. Por favor intente nuevamente.']
                ]
            ], 500);
        }
    }

    /**
     * Logout user (Revoke the token).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }
}
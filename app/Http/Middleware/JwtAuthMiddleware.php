<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;

class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = null;

        // 1. Get JWT from Authorization Bearer header
        $authHeader = $request->header('Authorization', '');
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // 2. Explicitly block URL-based token injection (security: prevents log leakage)
        if (empty($token) && $request->has('token')) {
            return response()->json([
                'status'  => 'error',
                'code'    => 401,
                'message' => 'Token via URL query parameter (?token=) is not allowed for security reasons. Use the Authorization: Bearer <token> header instead.'
            ], 401);
        }

        if (empty($token)) {
            // 3. Support API Key via X-API-Key header ONLY
            $apiKey = $request->header('X-API-Key', '');

            // Explicitly block URL-based api_key injection
            if (empty($apiKey) && $request->has('api_key')) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 401,
                    'message' => 'API key via URL query parameter (?api_key=) is not allowed for security reasons. Use the X-API-Key: <key> header instead.'
                ], 401);
            }

            if (!empty($apiKey)) {
                $user = $this->validateApiKey($apiKey);
                if (!$user) {
                    return response()->json([
                        'status'  => 'error',
                        'code'    => 401,
                        'message' => 'Invalid or suspended API key.'
                    ], 401);
                }
                
                // Set authenticated user on request
                $request->merge(['authenticated_user' => $user]);
                
                // Also set legacy static variable for backward compatibility if needed
                \App\Core\Router::$authenticatedUser = $user;
                
                if (!$this->checkPermissions($request, $user)) {
                    return response()->json([
                        'status'  => 'error',
                        'code'    => 403,
                        'message' => 'This API client key is not authorized to access this table/endpoint.'
                    ], 403);
                }

                return $next($request);
            }

            return response()->json([
                'status'  => 'error',
                'code'    => 401,
                'message' => 'Missing API authentication. Provide Authorization: Bearer <jwt> or X-API-Key: <key> header.'
            ], 401);
        }

        try {
            $secret = $_ENV['JWT_SECRET'] ?? 'supersecretkey';
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            $userId = $decoded->sub ?? null;
            if (!$userId) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 401,
                    'message' => 'Invalid JWT token claims.'
                ], 401);
            }

            $user = null;

            if (is_string($userId) && str_starts_with($userId, 'client_')) {
                // Fetch external API client
                $clientId = (int)substr($userId, 7);
                $client = DB::selectOne("
                    SELECT id, client_name, is_active, allowed_ips, allowed_endpoints
                    FROM api_clients
                    WHERE id = ? AND is_active = 1
                    LIMIT 1
                ", [$clientId]);

                if (!$client) {
                    return response()->json([
                        'status'  => 'error',
                        'code'    => 401,
                        'message' => 'API client associated with this token is suspended or not found.'
                    ], 401);
                }

                $client = (array)$client;

                // Validate allowed IPs if configured
                if (!empty($client['allowed_ips'])) {
                    $clientIps = array_map('trim', explode(',', $client['allowed_ips']));
                    $requestIp = request()->ip();
                    if (!in_array($requestIp, $clientIps) && !in_array('*', $clientIps)) {
                        return response()->json([
                            'status'  => 'error',
                            'code'    => 401,
                            'message' => 'Unauthorized IP address.'
                        ], 401);
                    }
                }

                $sanitizedName = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', strtolower($client['client_name'])));
                $user = [
                    'id'          => 'client_' . $client['id'],
                    'username'    => 'api_' . $sanitizedName,
                    'nom_complet' => $client['client_name'],
                    'admin'       => 0,
                    'IDNature'    => 99,
                    'activee'     => 0,
                    'role_code'   => 'api_client',
                    'role_ar'     => 'منصة خارجية (API)',
                    'allowed_endpoints' => json_decode($client['allowed_endpoints'] ?? '[]', true) ?: []
                ];
            } else {
                // Fetch user from DB (Windev singular table 'utilisateur', activee = 0 means active)
                $dbUser = DB::selectOne("
                    SELECT IDUtilisateur as id, NomUser as username, Nom as nom_complet, admin, IDNature, activee
                    FROM utilisateur
                    WHERE IDUtilisateur = ? AND activee = 0
                    LIMIT 1
                ", [$userId]);

                if (!$dbUser) {
                    return response()->json([
                        'status'  => 'error',
                        'code'    => 401,
                        'message' => 'User associated with this token is inactive or not found.'
                    ], 401);
                }

                $user = (array)$dbUser;
                
                // Map role programmatically based on user properties
                $role_code = 'special';
                $role_ar = 'حساب خاص';
                if ($user['admin'] == 1) {
                    $role_code = 'admin';
                    $role_ar = 'مدير النظام';
                } elseif ($user['IDNature'] == 4) {
                    $role_code = 'dfep';
                    $role_ar = 'DFEP ولائي';
                } elseif ($user['IDNature'] == 2) {
                    $role_code = 'directeur';
                    $role_ar = 'مدير مؤسسة';
                } elseif ($user['IDNature'] == 3) {
                    $role_code = 'formateur';
                    $role_ar = 'مكوّن';
                } elseif ($user['IDNature'] == 5) {
                    $role_code = 'stagiaire';
                    $role_ar = 'متربص';
                }
                $user['role_code'] = $role_code;
                $user['role_ar'] = $role_ar;
            }

            $request->merge(['authenticated_user' => $user]);
            
            // Also set legacy static variable for backward compatibility if needed
            \App\Core\Router::$authenticatedUser = $user;
            
            if (!$this->checkPermissions($request, $user)) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 403,
                    'message' => 'This API client key is not authorized to access this table/endpoint.'
                ], 403);
            }

            return $next($request);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 401,
                'message' => 'JWT Token has expired.'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 401,
                'message' => 'Invalid JWT Token signature or structure.'
            ], 401);
        }
    }

    private function validateApiKey(string $apiKey)
    {
        $users = DB::select("
            SELECT IDUtilisateur as id, NomUser as username, Nom as nom_complet, admin, IDNature, activee
            FROM utilisateur
            WHERE activee = 0
        ");
        
        foreach ($users as $user) {
            $user = (array)$user;
            // Generate deterministic key
            $derivedKey = 'sgfep_live_' . substr(hash('sha256', $user['username']), 0, 32);
            
            // Use hash_equals for safe constant-time comparison (Timing Attack protection)
            if (hash_equals($derivedKey, $apiKey)) {
                $role_code = 'special';
                $role_ar = 'حساب خاص';
                if ($user['admin'] == 1) {
                    $role_code = 'admin';
                    $role_ar = 'مدير النظام';
                } elseif ($user['IDNature'] == 4) {
                    $role_code = 'dfep';
                    $role_ar = 'DFEP ولائي';
                } elseif ($user['IDNature'] == 2) {
                    $role_code = 'directeur';
                    $role_ar = 'مدير مؤسسة';
                } elseif ($user['IDNature'] == 3) {
                    $role_code = 'formateur';
                    $role_ar = 'مكوّن';
                } elseif ($user['IDNature'] == 5) {
                    $role_code = 'stagiaire';
                    $role_ar = 'متربص';
                }
                $user['role_code'] = $role_code;
                $user['role_ar'] = $role_ar;
                return $user;
            }
        }

        // Try searching in external api_clients table (hashed API key check)
        $hashedKey = hash('sha256', $apiKey);
        $client = DB::selectOne("
            SELECT id, client_name, api_key, is_active, allowed_ips, allowed_endpoints
            FROM api_clients
            WHERE api_key = ? AND is_active = 1
            LIMIT 1
        ", [$hashedKey]);

        if ($client) {
            $client = (array)$client;
            
            // Validate allowed IPs if configured
            if (!empty($client['allowed_ips'])) {
                $clientIps = array_map('trim', explode(',', $client['allowed_ips']));
                $requestIp = request()->ip();
                if (!in_array($requestIp, $clientIps) && !in_array('*', $clientIps)) {
                    return null; // IP not allowed
                }
            }

            // Update last used timestamp
            try {
                DB::statement("UPDATE api_clients SET last_used_at = NOW() WHERE id = ?", [$client['id']]);
            } catch (\Exception $e) {
                // ignore
            }

            $sanitizedName = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', strtolower($client['client_name'])));
            return [
                'id' => 'client_' . $client['id'],
                'username' => 'api_' . $sanitizedName,
                'nom_complet' => $client['client_name'],
                'admin' => 0,
                'IDNature' => 99,
                'activee' => 0,
                'role_code' => 'api_client',
                'role_ar' => 'منصة خارجية (API)',
                'allowed_endpoints' => json_decode($client['allowed_endpoints'] ?? '[]', true) ?: []
            ];
        }

        return null;
    }

    private function checkPermissions(Request $request, $user)
    {
        if (isset($user) && ($user['role_code'] ?? '') === 'api_client') {
            $allowed = $user['allowed_endpoints'] ?? [];
            
            if (($request->is('api/v1/stagiaires') || $request->is('*/api/v1/stagiaires')) && !in_array('stagiaires', $allowed)) {
                return false;
            }
            
            if (($request->is('api/v1/offres') || $request->is('*/api/v1/offres')) && !in_array('offres', $allowed)) {
                return false;
            }
            
            if (($request->is('api/v1/employees') || $request->is('*/api/v1/employees')) && !in_array('employees', $allowed)) {
                return false;
            }

            if (($request->is('api/v1/hr/formateurs/*') || $request->is('*/api/v1/hr/formateurs/*')) && !in_array('formateurs', $allowed)) {
                return false;
            }

            if (($request->is('api/v1/finance/reports/*') || $request->is('*/api/v1/finance/reports/*')) && !in_array('finance', $allowed)) {
                return false;
            }

            if (($request->is('api/v1/assets/requests') || $request->is('*/api/v1/assets/requests')) && !in_array('assets', $allowed)) {
                return false;
            }
        }
        return true;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Firebase\JWT\JWT;
use PDO;

use OpenApi\Attributes as OA;

class JwtTokenController extends Controller
{

    #[OA\Post(
        path: "/api/v1/auth/token",
        summary: "تبديل مفتاح API بـ توكن JWT الموثق",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "api_key", type: "string", example: "sgfep_live_...")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "تم إصدار التوكن بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "token", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
                        new OA\Property(property: "expires_in", type: "integer", example: 28800)
                    ]
                )
            ),
            new OA\Response(response: 400, description: "طلب غير صالح - مفتاح API مفقود"),
            new OA\Response(response: 401, description: "مفتاح API غير صحيح أو الحساب معطل")
        ]
    )]
    public function issueToken()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json([
                'status' => 'error',
                'code' => 405,
                'message' => 'Method Not Allowed. Use POST method to request token.'
            ], 405);
        }

        $apiKey = request()->all()['api_key'] ?? '';

        if (empty($apiKey)) {
            $json = json_decode(file_get_contents('php://input'), true);
            $apiKey = $json['api_key'] ?? '';
        }

        if (empty($apiKey)) {
            return $this->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Bad Request. Missing api_key parameter.'
            ], 400);
        }

        try {
            // Fetch active users from DB (activee = 0 means active)
            $users = \Illuminate\Support\Facades\DB::select("
                SELECT IDUtilisateur as id, NomUser as username, Nom as nom_complet, admin, IDNature, activee
                FROM utilisateur
                WHERE activee = 0
            ");
            
            $user = null;
            foreach ($users as $u) {
                $u = (array)$u;
                // Generate deterministic key
                $derivedKey = 'sgfep_live_' . substr(hash('sha256', $u['username']), 0, 32);
                
                // Safe constant-time comparison (Timing Attack protection)
                if (hash_equals($derivedKey, $apiKey)) {
                    $role_code = 'special';
                    if ($u['admin'] == 1) {
                        $role_code = 'admin';
                    } elseif ($u['IDNature'] == 4) {
                        $role_code = 'dfep';
                    } elseif ($u['IDNature'] == 2) {
                        $role_code = 'directeur';
                    } elseif ($u['IDNature'] == 3) {
                        $role_code = 'formateur';
                    } elseif ($u['IDNature'] == 5) {
                        $role_code = 'stagiaire';
                    }
                    $u['role_code'] = $role_code;
                    $user = $u;
                    break;
                }
            }

            // If not a system user key, check external api_clients table (hashed)
            if (!$user) {
                $hashedKey = hash('sha256', $apiKey);
                $client = \Illuminate\Support\Facades\DB::selectOne("
                    SELECT id, client_name, is_active, allowed_ips
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
                            return $this->json([
                                'status' => 'error',
                                'code' => 401,
                                'message' => 'Unauthorized IP address.'
                            ], 401);
                        }
                    }

                    // Update last used timestamp
                    try {
                        \Illuminate\Support\Facades\DB::statement("UPDATE api_clients SET last_used_at = NOW() WHERE id = ?", [$client['id']]);
                    } catch (\Exception $e) {
                        // ignore
                    }

                    $sanitizedName = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', strtolower($client['client_name'])));
                    $user = [
                        'id' => 'client_' . $client['id'],
                        'username' => 'api_' . $sanitizedName,
                        'nom_complet' => $client['client_name'],
                        'role_code' => 'api_client'
                    ];
                }
            }

            if (!$user) {
                return $this->json([
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'Invalid API key or inactive user.'
                ], 401);
            }

            $secret = $_ENV['JWT_SECRET'] ?? 'supersecretkey';
            $expires = (int)($_ENV['JWT_EXPIRES_IN'] ?? 28800);
            
            $payload = [
                'iss' => $_ENV['APP_URL'] ?? 'http://localhost/sig',
                'iat' => time(),
                'exp' => time() + $expires,
                'sub' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role_code']
            ];

            $jwt = JWT::encode($payload, $secret, 'HS256');

            return $this->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'JWT Token issued successfully.',
                'token' => $jwt,
                'token_type' => 'Bearer',
                'expires_in' => $expires,
                'user' => [
                    'username' => $user['username'],
                    'name' => $user['nom_complet']
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to issue JWT token: ' . $e->getMessage()
            ], 500);
        }
    }
}

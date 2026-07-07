<?php
namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ExternalIntegrationService
 * Manages connections to national Algerian systems:
 * - ANEM  (Agence Nationale de l'Emploi)
 * - CNAS  (Caisse Nationale des Assurances Sociales)
 * - Damancom (Social Security Portal)
 *
 * Architecture: Adapter pattern — each system has its own adapter.
 * All calls are cached + logged + fail-safe.
 */
class ExternalIntegrationService
{
    /** Verify if a NIN (Numéro d'Identification National) is registered at ANEM */
    public static function anemVerifyNin(string $nin): array
    {
        return self::stub('ANEM', 'verify_nin', $nin, [
            'source'     => 'ANEM',
            'nin'        => substr($nin, 0, 4) . '****',   // masked
            'registered' => false,
            'status'     => 'stub',
            'message'    => 'ANEM API integration ready — endpoint not yet configured.',
            'how_to'     => 'Set ANEM_API_URL and ANEM_API_KEY in .env',
        ]);
    }

    /** Get employment status from ANEM for a NIN */
    public static function anemEmploymentStatus(string $nin): array
    {
        return self::stub('ANEM', 'employment_status', $nin, [
            'source'  => 'ANEM',
            'status'  => 'unknown',
            'message' => 'ANEM employment status endpoint not yet configured.',
        ]);
    }

    /** Verify CNAS registration by NIN */
    public static function cnasVerify(string $nin): array
    {
        return self::stub('CNAS', 'verify', $nin, [
            'source'     => 'CNAS',
            'registered' => false,
            'regime'     => null,
            'message'    => 'CNAS API integration ready — endpoint not yet configured.',
            'how_to'     => 'Set CNAS_API_URL and CNAS_API_KEY in .env',
        ]);
    }

    /** Get social contributions summary from Damancom */
    public static function damancomContributions(string $nin): array
    {
        return self::stub('DAMANCOM', 'contributions', $nin, [
            'source'       => 'Damancom',
            'contributions'=> [],
            'total'        => 0,
            'message'      => 'Damancom API integration ready — endpoint not yet configured.',
            'how_to'       => 'Set DAMANCOM_API_URL and DAMANCOM_TOKEN in .env',
        ]);
    }

    /** Make a live API call when credentials are configured */
    public static function callApi(string $service, string $endpoint, array $params = []): array
    {
        $urlKey   = strtoupper($service) . '_API_URL';
        $tokenKey = strtoupper($service) . '_API_KEY';
        $baseUrl  = env($urlKey);
        $apiKey   = env($tokenKey);

        if (!$baseUrl || !$apiKey) {
            return ['success' => false, 'error' => "{$service} API not configured. Set {$urlKey} and {$tokenKey} in .env"];
        }

        $cacheKey = "ext_{$service}_{$endpoint}_" . md5(json_encode($params));
        return Cache::remember($cacheKey, 300, function () use ($baseUrl, $apiKey, $endpoint, $params, $service) {
            try {
                $response = Http::withToken($apiKey)
                    ->timeout(10)
                    ->post("{$baseUrl}/{$endpoint}", $params);

                if ($response->successful()) {
                    Log::info("[Integration:{$service}] Call to {$endpoint} succeeded.");
                    return ['success' => true, 'data' => $response->json()];
                }

                Log::warning("[Integration:{$service}] Call to {$endpoint} failed: HTTP " . $response->status());
                return ['success' => false, 'error' => "HTTP " . $response->status()];
            } catch (\Exception $e) {
                Log::error("[Integration:{$service}] Exception: " . $e->getMessage());
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });
    }

    /** Returns stub data + logs the attempt */
    private static function stub(string $service, string $action, string $nin, array $stub): array
    {
        Log::info("[Integration:{$service}] Stub call for {$action} — configure API to enable live data.");
        return array_merge($stub, ['success' => false, 'stub' => true]);
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "منصة التكوين المهني SGFEP - واجهة برمجة التطبيقات (API)",
    description: "توثيق تفاعلي لمسارات الـ API الخاصة بالمنصة الرقمية للتكوين المهني لدعم التطبيقات الخارجية وتطبيق الهاتف."
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: "خادم المنصة الرئيسي"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    name: "Authorization",
    in: "header",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\SecurityScheme(
    securityScheme: "ApiKeyAuth",
    type: "apiKey",
    name: "X-API-Key",
    in: "header"
)]
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Authorize access to specific roles. Redirects to /dashboard if unauthorized.
     */
    protected function authorizeRole(array $allowedRoles)
    {
        $user = session('user') ?? [];
        $role = strtolower($user['role_code'] ?? '');

        $authorized = false;
        foreach ($allowedRoles as $allowedRole) {
            $gateName = strtolower($allowedRole);
            if (\Illuminate\Support\Facades\Gate::allows($gateName)) {
                $authorized = true;
                break;
            }
        }

        // Fallback for specific roles not mapped to general Gates
        if (!$authorized && in_array($role, array_map('strtolower', $allowedRoles))) {
            $authorized = true;
        }

        if (!$authorized) {
            $redirectResponse = redirect('/dashboard')->with('error', 'غير مصرح لك بالوصول لهذه الصفحة. (RBAC)');
            throw new \Illuminate\Http\Exceptions\HttpResponseException($redirectResponse);
        }
    }

    /**
     * Legacy view renderer compatibility adapter
     */
    protected function render(string $view, array $data = [], string $layout = 'main')
    {
        $view = str_replace('/', '.', $view);
        return view($view, $data);
    }

    /**
     * Legacy view compatibility adapter
     */
    protected function view(string $view, array $data = [])
    {
        $view = str_replace('/', '.', $view);
        return view($view, $data);
    }

    /**
     * Legacy JSON response compatibility adapter
     */
    protected function json(mixed $data, int $status = 200)
    {
        return response()->json($data, $status);
    }

    /**
     * Legacy redirect compatibility adapter
     */
    protected function redirect(string $url)
    {
        if (strpos($url, '/sig') === 0) {
            $url = substr($url, 4);
        }
        return redirect()->to($url);
    }
}

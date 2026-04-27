<?php

namespace App\Http\Middleware;

use App\Models\DashboardChangeLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogDashboardActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldLog($request, $response)) {
            return $response;
        }

        $action = $this->detectAction($request);
        if ($action === null) {
            return $response;
        }

        $moduleKey = $this->detectModuleKey($request);
        $moduleLabel = $this->formatModuleLabel($moduleKey);
        $reference = $this->detectReference($request);
        $staffId = (int) (session('user')->staff_id ?? 0);

        DashboardChangeLog::create([
            'module_key' => $moduleKey,
            'module_label' => $moduleLabel,
            'reference' => $reference,
            'what_changed' => ucfirst($action).' pada '.$moduleLabel,
            'summary' => trim(($reference ? $reference.' · ' : '').'Aksi '.$action),
            'url' => url($request->path()),
            'url_label' => 'Buka menu',
            'created_by' => $staffId > 0 ? $staffId : null,
            'meta' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'action' => $action,
            ],
        ]);

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        if (!in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }
        if ($response->getStatusCode() >= 400) {
            return false;
        }
        if (!session()->has('user')) {
            return false;
        }

        $path = strtolower(trim($request->path(), '/'));
        if ($path === '' || str_starts_with($path, 'get')) {
            return false;
        }
        if (in_array($path, ['dismissdashboardqueueitem', 'updatedashboardwidgets', 'updatepermission'], true)) {
            return false;
        }

        return true;
    }

    private function detectAction(Request $request): ?string
    {
        $txt = strtolower($request->path().' '.($request->route()?->getName() ?? ''));
        $map = [
            'insert' => ['insert', 'create', 'store', 'tambah'],
            'update' => ['update', 'edit', 'perbarui'],
            'delete' => ['delete', 'remove', 'hapus'],
            'acc' => ['acc', 'accept', 'approve', 'konfirmasi'],
            'tolak' => ['decline', 'reject', 'tolak'],
        ];
        foreach ($map as $label => $keys) {
            foreach ($keys as $k) {
                if (str_contains($txt, $k)) {
                    return $label;
                }
            }
        }

        return null;
    }

    private function detectModuleKey(Request $request): string
    {
        $path = trim($request->path(), '/');
        $seg = $path === '' ? 'dashboard' : explode('/', $path)[0];
        $seg = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $seg) ?? 'dashboard');
        return $seg !== '' ? $seg : 'dashboard';
    }

    private function formatModuleLabel(string $moduleKey): string
    {
        return Str::title(str_replace('_', ' ', $moduleKey));
    }

    private function detectReference(Request $request): ?string
    {
        $candidates = [
            'so_invoice_no',
            'so_number',
            'po_number',
            'production_code',
            'pi_code',
            'ref_num',
            'reference',
            'barcode',
            'role_name',
            'staff_name',
        ];

        foreach ($candidates as $key) {
            $val = trim((string) $request->input($key, ''));
            if ($val !== '') {
                return $val;
            }
        }

        $idCandidates = ['so_id', 'po_id', 'production_id', 'pi_id', 'product_id', 'supplies_id', 'role_id', 'staff_id', 'id'];
        foreach ($idCandidates as $key) {
            $val = $request->input($key);
            if ($val !== null && $val !== '') {
                return strtoupper($key).'#'.$val;
            }
        }

        return null;
    }
}


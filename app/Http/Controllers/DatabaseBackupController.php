<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function __invoke(Request $request, DatabaseBackupService $backup): BinaryFileResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Solo el superadministrador puede descargar respaldos.');

        $path = $backup->create();

        return response()->download(
            $path,
            'respaldo-'.now()->format('Y-m-d_H-i-s').'.sql',
            ['Content-Type' => 'application/sql']
        )->deleteFileAfterSend(true);
    }
}

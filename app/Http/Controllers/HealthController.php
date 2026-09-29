<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Gezondheidscontrole voor de uitrol.
 *
 * /up van Laravel zegt alleen dat de app opstart. De uitrol wil twee dingen
 * méér weten: draait de release die net live gezet is (en niet een oudere
 * uit een cache), en bereikt de app haar database. Faalt een van beide, dan
 * zet het uitrolscript de vorige release terug.
 *
 * Bewust karig: geen versies, paden of foutmeldingen naar buiten.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = $this->databaseBereikbaar();

        return response()->json([
            'status' => $database ? 'ok' : 'fout',
            'release' => $this->release(),
            'checks' => ['database' => $database ? 'ok' : 'fout'],
        ], $database ? 200 : 503)->header('Cache-Control', 'no-store');
    }

    private function databaseBereikbaar(): bool
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Het uitrolscript schrijft de release-id (commit) in het bestand RELEASE.
     * Lokaal en in tests bestaat dat niet.
     */
    private function release(): string
    {
        $bestand = base_path('RELEASE');

        return is_file($bestand) ? trim((string) file_get_contents($bestand)) : 'dev';
    }
}

<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteMensualControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_downloads_the_report_as_pdf(): void
    {
        $user = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($user)->create();

        $this->actingAs($user)
            ->get(route('reportes.mensual', ['empresa' => $empresa, 'anio' => 2026, 'mes' => 10, 'formato' => 'pdf']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('reporte-2026-10.pdf');
    }

    public function test_downloads_the_report_as_excel(): void
    {
        $user = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($user)->create();

        $respuesta = $this->actingAs($user)
            ->get(route('reportes.mensual', ['empresa' => $empresa, 'anio' => 2026, 'mes' => 10, 'formato' => 'xlsx']))
            ->assertOk()
            ->assertDownload('reporte-2026-10.xlsx');

        $this->assertStringStartsWith('PK', file_get_contents($respuesta->baseResponse->getFile()->getPathname()));
    }

    public function test_forbids_the_report_of_another_empresa(): void
    {
        $user = User::factory()->create();
        Empresa::factory()->conUsuario($user)->create();
        $ajena = Empresa::factory()->create();

        $this->actingAs($user)
            ->get(route('reportes.mensual', ['empresa' => $ajena, 'anio' => 2026, 'mes' => 10, 'formato' => 'pdf']))
            ->assertForbidden();
    }
}

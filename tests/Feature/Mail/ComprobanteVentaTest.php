<?php

namespace Tests\Feature\Mail;

use App\Mail\ComprobanteVenta;
use App\Models\DetalleVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprobanteVentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_names_the_venta_in_the_subject_and_body(): void
    {
        $venta = DetalleVenta::factory()->create()->venta;

        $mail = new ComprobanteVenta($venta);

        $mail->assertHasSubject("Comprobante de venta 00001 · {$venta->empresa->nombre}");
        $mail->assertSeeInHtml('00001');
    }

    /**
     * El PDF lleva la hora en que se generó, así que se revisa que sea un PDF y su nombre, no sus bytes.
     */
    public function test_attaches_the_comprobante_as_a_pdf(): void
    {
        $venta = DetalleVenta::factory()->create()->venta;

        $adjuntos = (new ComprobanteVenta($venta))->attachments();

        $this->assertCount(1, $adjuntos);
        $this->assertSame('comprobante-00001.pdf', $adjuntos[0]->as);
        $this->assertSame('application/pdf', $adjuntos[0]->mime);
        $this->assertStringStartsWith('%PDF', $adjuntos[0]->attachWith(fn () => null, fn (\Closure $datos): string => $datos()));
    }
}

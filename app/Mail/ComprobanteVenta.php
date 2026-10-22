<?php

namespace App\Mail;

use App\Http\Controllers\ComprobanteVentaController;
use App\Models\Venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Envía al cliente su comprobante de venta en PDF.
 */
class ComprobanteVenta extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Venta $venta) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Comprobante de venta {$this->venta->numeroFormateado()} · {$this->venta->empresa->nombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.comprobante-venta',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => ComprobanteVentaController::pdf($this->venta)->output(),
                "comprobante-{$this->venta->numeroFormateado()}.pdf",
            )->withMime('application/pdf'),
        ];
    }
}

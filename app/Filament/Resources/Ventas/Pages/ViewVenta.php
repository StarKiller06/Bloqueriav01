<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use App\Mail\ComprobanteVenta;
use App\Models\Venta;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;

class ViewVenta extends ViewRecord
{
    protected static string $resource = VentaResource::class;

    public function getTitle(): string
    {
        /** @var Venta $venta */
        $venta = $this->getRecord();

        return "Venta {$venta->numeroFormateado()}";
    }

    protected function getHeaderActions(): array
    {
        return [
            VentaResource::abonarAction(),
            Action::make('comprobante')
                ->label('Comprobante PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->url(fn (Venta $record): string => route('ventas.comprobante', $record))
                ->openUrlInNewTab(),
            Action::make('enviar')
                ->label('Enviar por correo')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->fillForm(fn (Venta $record): array => ['email' => $record->cliente?->email])
                ->schema([
                    TextInput::make('email')
                        ->label('Correo')
                        ->email()
                        ->required(),
                ])
                ->action(fn (Venta $record, array $data) => Mail::to($data['email'])->send(new ComprobanteVenta($record)))
                ->successNotificationTitle('Comprobante enviado'),
            ActionGroup::make([
                VentaResource::anularAction(),
            ]),
        ];
    }
}

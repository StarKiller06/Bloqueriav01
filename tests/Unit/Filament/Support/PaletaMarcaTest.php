<?php

namespace Tests\Unit\Filament\Support;

use App\Filament\Support\PaletaMarca;
use Filament\Support\Colors\Color;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaletaMarcaTest extends TestCase
{
    /**
     * Combinaciones de texto y fondo que usa el panel, en modo claro y oscuro.
     *
     * @return array<string, array{string, string}>
     */
    public static function combinaciones(): array
    {
        $primaria = PaletaMarca::primaria();
        $gris = PaletaMarca::gris();

        return [
            'texto blanco en botón principal' => ['#ffffff', $primaria[600]],
            'enlace sobre blanco' => [$primaria[600], '#ffffff'],
            'enlace sobre fondo gris claro' => [$primaria[700], $gris[50]],
            'texto secundario sobre blanco' => [$gris[500], '#ffffff'],
            'texto principal sobre blanco' => [$gris[950], '#ffffff'],
            'insignia clara' => [$primaria[700], $primaria[50]],
            'oscuro: enlace sobre fondo' => [$primaria[400], $gris[900]],
            'oscuro: enlace sobre fondo más oscuro' => [$primaria[400], $gris[950]],
            'oscuro: texto secundario' => [$gris[400], $gris[900]],
            'oscuro: texto principal' => ['#ffffff', $gris[900]],
            'oscuro: insignia' => [$primaria[300], $primaria[950]],
            'nombre de la marca' => [$primaria[800], '#ffffff'],
        ];
    }

    #[DataProvider('combinaciones')]
    public function test_every_text_combination_meets_wcag_aa(string $texto, string $fondo): void
    {
        $this->assertGreaterThanOrEqual(4.5, Color::calculateContrastRatio($texto, $fondo));
    }

    public function test_the_button_green_matches_the_logo(): void
    {
        $this->assertLessThan(0.03, $this->distancia(Color::convertToOklch(PaletaMarca::primaria()[600]), Color::convertToOklch(PaletaMarca::VERDE_MARCA)));
    }

    public function test_each_step_is_darker_than_the_previous_one(): void
    {
        foreach ([PaletaMarca::primaria(), PaletaMarca::gris()] as $paleta) {
            $luminosidades = array_map(fn (string $color): float => (float) sscanf($color, 'oklch(%f')[0], array_values($paleta));

            $ordenadas = $luminosidades;
            rsort($ordenadas);

            $this->assertSame($ordenadas, $luminosidades);
        }
    }

    private function distancia(string $a, string $b): float
    {
        [$la, $ca, $ha] = sscanf($a, 'oklch(%f %f %f)');
        [$lb, $cb, $hb] = sscanf($b, 'oklch(%f %f %f)');

        return abs($la - $lb) + abs($ca - $cb) + abs($ha - $hb) / 360;
    }
}

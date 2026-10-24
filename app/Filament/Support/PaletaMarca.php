<?php

namespace App\Filament\Support;

/**
 * Colores del panel sacados del logo de la bloquería.
 *
 * Cada paleta usa una sola escalera de luminosidad (de 50, casi blanco, a 950, casi negro)
 * y toma del logo solo el tono. Así el contraste entre tonos es el mismo en todas las paletas,
 * y cualquier combinación que Filament use para texto pasa la norma WCAG AA (4,5:1).
 */
final class PaletaMarca
{
    /**
     * Verde del hexágono del logo (#247830).
     */
    public const string VERDE_MARCA = '#247830';

    /**
     * Verde de "LA PRINCIPAL" en el logo (#0b4a24).
     */
    public const string VERDE_PROFUNDO = '#0b4a24';

    /**
     * Tono (hue OKLCH) de los verdes del logo.
     */
    private const float TONO_VERDE = 148.0;

    /**
     * Luminosidad OKLCH por tono. El 600, que usan los botones, queda igual al verde del hexágono
     * y el 850 al verde profundo de las letras.
     *
     * @var array<int, float>
     */
    private const array ESCALERA = [
        50 => 0.975,
        100 => 0.945,
        200 => 0.895,
        300 => 0.815,
        400 => 0.715,
        500 => 0.600,
        600 => 0.510,
        700 => 0.445,
        800 => 0.390,
        900 => 0.330,
        950 => 0.250,
    ];

    /**
     * Saturación (croma) relativa por tono: máxima en el centro, baja en los extremos.
     *
     * @var array<int, float>
     */
    private const array CURVA_CROMA = [
        50 => 0.10, 100 => 0.20, 200 => 0.38, 300 => 0.62, 400 => 0.85, 500 => 0.98,
        600 => 1.00, 700 => 0.92, 800 => 0.80, 900 => 0.66, 950 => 0.50,
    ];

    /**
     * El gris lleva el mismo tono verde, apenas perceptible, como el fondo del logo.
     * Su escalera es un poco más oscura en el centro porque el gris 500 se usa para texto secundario.
     *
     * @var array<int, float>
     */
    private const array ESCALERA_GRIS = [
        50 => 0.985,
        100 => 0.965,
        200 => 0.925,
        300 => 0.870,
        400 => 0.725,
        500 => 0.555,
        600 => 0.460,
        700 => 0.380,
        800 => 0.290,
        900 => 0.220,
        950 => 0.155,
    ];

    /**
     * @return array<int, string>
     */
    public static function primaria(): array
    {
        return self::paleta(self::ESCALERA, croma: 0.136, tono: self::TONO_VERDE);
    }

    /**
     * @return array<int, string>
     */
    public static function gris(): array
    {
        return self::paleta(self::ESCALERA_GRIS, croma: 0.012, tono: self::TONO_VERDE);
    }

    /**
     * @param  array<int, float>  $escalera
     * @return array<int, string>
     */
    private static function paleta(array $escalera, float $croma, float $tono): array
    {
        $paleta = [];

        foreach ($escalera as $nivel => $luminosidad) {
            $paleta[$nivel] = sprintf('oklch(%.3f %.3f %.1f)', $luminosidad, $croma * self::CURVA_CROMA[$nivel], $tono);
        }

        return $paleta;
    }
}

<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Fixtures;

/**
 * Генерирует картинки-заглушки локально через GD (без внешних URL), как в symphony-shop.
 */
final class SeedImage
{
    /**
     * @param array{0: int, 1: int, 2: int} $rgb  базовый цвет категории
     * @param positive-int                  $size
     */
    public static function png(array $rgb, int $seed, int $size = 800): string
    {
        $img = imagecreatetruecolor($size, $size);
        \assert(false !== $img);
        imageantialias($img, true);

        // Вертикальный градиент от базового цвета к светлому.
        for ($y = 0; $y < $size; ++$y) {
            $t = $y / $size;
            $color = (int) imagecolorallocate(
                $img,
                self::channel($rgb[0] + (255 - $rgb[0]) * $t * 0.75),
                self::channel($rgb[1] + (255 - $rgb[1]) * $t * 0.75),
                self::channel($rgb[2] + (255 - $rgb[2]) * $t * 0.75),
            );
            imageline($img, 0, $y, $size, $y, $color);
        }

        mt_srand($seed);
        $white = (int) imagecolorallocatealpha($img, 255, 255, 255, 70);
        $dark = (int) imagecolorallocatealpha($img, self::channel($rgb[0] * 0.5), self::channel($rgb[1] * 0.5), self::channel($rgb[2] * 0.5), 40);

        // Декоративные круги.
        for ($i = 0; $i < 5; ++$i) {
            $d = mt_rand((int) ($size * 0.15), (int) ($size * 0.45));
            imagefilledellipse($img, mt_rand(0, $size), mt_rand(0, $size), $d, $d, $white);
        }

        // «Предмет» по центру: скруглённый прямоугольник + круг.
        $w = (int) ($size * (0.35 + mt_rand(0, 15) / 100));
        $h = (int) ($size * (0.30 + mt_rand(0, 20) / 100));
        $x = (int) (($size - $w) / 2);
        $y = (int) (($size - $h) / 2);
        $r = (int) ($size * 0.05);
        imagefilledrectangle($img, $x + $r, $y, $x + $w - $r, $y + $h, $dark);
        imagefilledrectangle($img, $x, $y + $r, $x + $w, $y + $h - $r, $dark);
        foreach ([[$x + $r, $y + $r], [$x + $w - $r, $y + $r], [$x + $r, $y + $h - $r], [$x + $w - $r, $y + $h - $r]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $dark);
        }
        $c = (int) ($size * 0.12);
        imagefilledellipse($img, (int) ($size / 2), (int) ($size / 2), $c, $c, (int) imagecolorallocatealpha($img, 255, 255, 255, 20));

        ob_start();
        imagepng($img, null, 6);

        return (string) ob_get_clean();
    }

    /**
     * Компонента цвета в диапазоне 0..255.
     *
     * @return int<0, 255>
     */
    private static function channel(float|int $value): int
    {
        return max(0, min(255, (int) $value));
    }
}

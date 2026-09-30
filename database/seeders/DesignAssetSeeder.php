<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DesignAssetSeeder extends Seeder
{
    /**
     * Palette definition for each foil / laser engraving color.
     *
     * @var array<string, array{main: array{int, int, int}, light: array{int, int, int}, dark: array{int, int, int}}>
     */
    private array $palettes = [
        'gold' => [
            'main' => [230, 194, 101],
            'light' => [255, 235, 165],
            'dark' => [165, 125, 45],
        ],
        'silver' => [
            'silver' => [212, 212, 216],
            'main' => [212, 212, 216],
            'light' => [245, 245, 250],
            'dark' => [140, 140, 150],
        ],
        'black' => [
            'main' => [35, 35, 38],
            'light' => [70, 70, 75],
            'dark' => [15, 15, 18],
        ],
        'white' => [
            'main' => [250, 250, 255],
            'light' => [255, 255, 255],
            'dark' => [190, 195, 205],
        ],
        'navy' => [
            'main' => [30, 58, 95],
            'light' => [65, 105, 165],
            'dark' => [15, 30, 55],
        ],
        'blue' => [
            'main' => [2, 132, 199],
            'light' => [56, 189, 248],
            'dark' => [3, 105, 161],
        ],
        'red' => [
            'main' => [220, 38, 38],
            'light' => [248, 113, 113],
            'dark' => [153, 27, 27],
        ],
        'copper' => [
            'main' => [184, 115, 51],
            'light' => [225, 155, 90],
            'dark' => [130, 70, 25],
        ],
        'green' => [
            'main' => [22, 163, 74],
            'light' => [74, 222, 128],
            'dark' => [21, 128, 61],
        ],
        'yellow' => [
            'main' => [234, 179, 8],
            'light' => [254, 240, 138],
            'dark' => [161, 98, 7],
        ],
    ];

    /**
     * All 30 seeded design image paths with motif and color key.
     *
     * @var array<string, array{motif: string, color: string}>
     */
    private array $assets = [
        'design-images/btc-classic-gold.png' => ['motif' => 'btc-classic', 'color' => 'gold'],
        'design-images/btc-classic-black.png' => ['motif' => 'btc-classic', 'color' => 'black'],
        'design-images/btc-classic-navy.png' => ['motif' => 'btc-classic', 'color' => 'navy'],
        'design-images/btc-gold-black.png' => ['motif' => 'btc-gold', 'color' => 'black'],
        'design-images/btc-gold-silver.png' => ['motif' => 'btc-gold', 'color' => 'silver'],
        'design-images/eth-diamond-silver.png' => ['motif' => 'eth-diamond', 'color' => 'silver'],
        'design-images/eth-diamond-black.png' => ['motif' => 'eth-diamond', 'color' => 'black'],
        'design-images/eth-diamond-white.png' => ['motif' => 'eth-diamond', 'color' => 'white'],
        'design-images/eth-neon-blue.png' => ['motif' => 'eth-neon', 'color' => 'blue'],
        'design-images/eth-neon-black.png' => ['motif' => 'eth-neon', 'color' => 'black'],
        'design-images/doge-gold.png' => ['motif' => 'doge', 'color' => 'gold'],
        'design-images/doge-black.png' => ['motif' => 'doge', 'color' => 'black'],
        'design-images/doge-red.png' => ['motif' => 'doge', 'color' => 'red'],
        'design-images/mountain-white.png' => ['motif' => 'mountain', 'color' => 'white'],
        'design-images/mountain-navy.png' => ['motif' => 'mountain', 'color' => 'navy'],
        'design-images/ocean-blue.png' => ['motif' => 'ocean', 'color' => 'blue'],
        'design-images/ocean-silver.png' => ['motif' => 'ocean', 'color' => 'silver'],
        'design-images/grid-black.png' => ['motif' => 'grid', 'color' => 'black'],
        'design-images/grid-copper.png' => ['motif' => 'grid', 'color' => 'copper'],
        'design-images/grid-silver.png' => ['motif' => 'grid', 'color' => 'silver'],
        'design-images/neon-black.png' => ['motif' => 'neon', 'color' => 'black'],
        'design-images/neon-green.png' => ['motif' => 'neon', 'color' => 'green'],
        'design-images/mustang-red.png' => ['motif' => 'mustang', 'color' => 'red'],
        'design-images/mustang-black.png' => ['motif' => 'mustang', 'color' => 'black'],
        'design-images/mustang-silver.png' => ['motif' => 'mustang', 'color' => 'silver'],
        'design-images/lambo-yellow.png' => ['motif' => 'lambo', 'color' => 'yellow'],
        'design-images/lambo-black.png' => ['motif' => 'lambo', 'color' => 'black'],
        'design-images/porsche-red.png' => ['motif' => 'porsche', 'color' => 'red'],
        'design-images/porsche-white.png' => ['motif' => 'porsche', 'color' => 'white'],
        'design-images/porsche-black.png' => ['motif' => 'porsche', 'color' => 'black'],
    ];

    public function run(): void
    {
        $disk = Storage::disk('public');
        $generated = 0;

        foreach ($this->assets as $path => $spec) {
            if ($disk->exists($path)) {
                continue;
            }

            $binary = $this->renderAsset($spec['motif'], $spec['color']);
            if ($binary !== null) {
                $disk->put($path, $binary);
                $generated++;
            }
        }

        if ($generated > 0) {
            echo "[DesignAssetSeeder] Generated {$generated} missing design assets on public disk.\n";
        }
    }

    /**
     * Render transparent 800x800 laser engraving PNG for motif and color.
     */
    public function renderAsset(string $motif, string $colorKey): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $size = 800;
        $canvas = imagecreatetruecolor($size, $size);

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);
        imagealphablending($canvas, true);

        if (function_exists('imageantialias')) {
            imageantialias($canvas, true);
        }

        $palette = $this->palettes[$colorKey] ?? $this->palettes['gold'];
        $mainColor = imagecolorallocate($canvas, ...$palette['main']);
        $lightColor = imagecolorallocate($canvas, ...$palette['light']);
        $darkColor = imagecolorallocate($canvas, ...$palette['dark']);

        imagesetthickness($canvas, 3);

        switch ($motif) {
            case 'btc-classic':
                $this->drawBtcClassic($canvas, $mainColor, $lightColor, $darkColor);
                break;
            case 'btc-gold':
                $this->drawBtcGold($canvas, $mainColor, $lightColor, $darkColor);
                break;
            case 'eth-diamond':
                $this->drawEthDiamond($canvas, $mainColor, $lightColor, $darkColor);
                break;
            case 'eth-neon':
                $this->drawEthNeon($canvas, $mainColor, $lightColor, $darkColor);
                break;
            case 'doge':
                $this->drawDoge($canvas, $mainColor, $lightColor, $darkColor);
                break;
            case 'mountain':
                $this->drawMountain($canvas, $mainColor, $lightColor, $darkColor);
                break;
            case 'ocean':
                $this->drawOcean($canvas, $mainColor, $lightColor, $darkColor);
                break;
            case 'grid':
                $this->drawGrid($canvas, $mainColor, $lightColor, $darkColor);
                break;
            case 'neon':
                $this->drawNeon($canvas, $mainColor, $lightColor, $darkColor);
                break;
            case 'mustang':
                $this->drawMustang($canvas, $mainColor, $lightColor, $darkColor);
                break;
            case 'lambo':
                $this->drawLambo($canvas, $mainColor, $lightColor, $darkColor);
                break;
            case 'porsche':
                $this->drawPorsche($canvas, $mainColor, $lightColor, $darkColor);
                break;
            default:
                $this->drawBtcClassic($canvas, $mainColor, $lightColor, $darkColor);
        }

        ob_start();
        imagepng($canvas);
        $binary = (string) ob_get_clean();
        imagedestroy($canvas);

        return $binary;
    }

    private function drawBtcClassic($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;
        $cy = 400;

        // Concentric outer rings
        imagesetthickness($canvas, 4);
        imageellipse($canvas, $cx, $cy, 560, 560, $main);
        imagesetthickness($canvas, 2);
        imageellipse($canvas, $cx, $cy, 520, 520, $dark);
        imageellipse($canvas, $cx, $cy, 460, 460, $light);

        // Radial ticks around perimeter
        imagesetthickness($canvas, 2);
        for ($i = 0; $i < 36; $i++) {
            $angle = ($i * 10) * M_PI / 180;
            $r1 = ($i % 3 === 0) ? 230 : 245;
            $r2 = 260;
            $x1 = (int) round($cx + cos($angle) * $r1);
            $y1 = (int) round($cy + sin($angle) * $r1);
            $x2 = (int) round($cx + cos($angle) * $r2);
            $y2 = (int) round($cy + sin($angle) * $r2);
            imageline($canvas, $x1, $y1, $x2, $y2, ($i % 3 === 0) ? $light : $main);
        }

        // Circuit tracks
        $tracks = [
            [[$cx - 230, $cy - 60], [$cx - 160, $cy - 60], [$cx - 130, $cy - 90]],
            [[$cx - 230, $cy + 60], [$cx - 160, $cy + 60], [$cx - 130, $cy + 90]],
            [[$cx + 230, $cy - 60], [$cx + 160, $cy - 60], [$cx + 130, $cy - 90]],
            [[$cx + 230, $cy + 60], [$cx + 160, $cy + 60], [$cx + 130, $cy + 90]],
        ];
        foreach ($tracks as $pts) {
            for ($j = 0; $j < count($pts) - 1; $j++) {
                imageline($canvas, $pts[$j][0], $pts[$j][1], $pts[$j + 1][0], $pts[$j + 1][1], $dark);
            }
            imagefilledellipse($canvas, $pts[0][0], $pts[0][1], 10, 10, $light);
            imagefilledellipse($canvas, $pts[count($pts) - 1][0], $pts[count($pts) - 1][1], 8, 8, $main);
        }

        // Central Bitcoin symbol 'B'
        imagesetthickness($canvas, 8);
        // Vertical spine
        imageline($canvas, 360, 270, 360, 530, $main);
        // Top and bottom bars
        imageline($canvas, 380, 245, 380, 275, $light);
        imageline($canvas, 410, 245, 410, 275, $light);
        imageline($canvas, 380, 525, 380, 555, $light);
        imageline($canvas, 410, 525, 410, 555, $light);

        // Horizontal bars
        imageline($canvas, 360, 280, 420, 280, $main);
        imageline($canvas, 360, 400, 430, 400, $main);
        imageline($canvas, 360, 520, 420, 520, $main);

        // B upper loop
        imagesetthickness($canvas, 7);
        imagearc($canvas, 420, 340, 120, 120, 270, 90, $main);
        // B lower loop
        imagearc($canvas, 420, 460, 140, 120, 270, 90, $light);
    }

    private function drawBtcGold($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;
        $cy = 400;

        // Outer hexagon badge
        $hex = [];
        $r = 270;
        for ($i = 0; $i < 6; $i++) {
            $angle = ($i * 60 + 30) * M_PI / 180;
            $hex[] = (int) round($cx + cos($angle) * $r);
            $hex[] = (int) round($cy + sin($angle) * $r);
        }
        imagesetthickness($canvas, 5);
        imagepolygon($canvas, $hex, $main);

        // Inner hexagon
        $innerHex = [];
        $rInner = 240;
        for ($i = 0; $i < 6; $i++) {
            $angle = ($i * 60 + 30) * M_PI / 180;
            $innerHex[] = (int) round($cx + cos($angle) * $rInner);
            $innerHex[] = (int) round($cy + sin($angle) * $rInner);
        }
        imagesetthickness($canvas, 2);
        imagepolygon($canvas, $innerHex, $dark);

        // Facet lines from outer to inner
        for ($i = 0; $i < 6; $i++) {
            imageline($canvas, $hex[$i * 2], $hex[$i * 2 + 1], $innerHex[$i * 2], $innerHex[$i * 2 + 1], $light);
        }

        // Geometric stylized crypto B
        imagesetthickness($canvas, 8);
        imageline($canvas, 350, 280, 350, 520, $main);
        imageline($canvas, 350, 280, 420, 280, $main);
        imageline($canvas, 420, 280, 460, 335, $light);
        imageline($canvas, 460, 335, 420, 395, $light);
        imageline($canvas, 420, 395, 350, 395, $main);
        imageline($canvas, 420, 395, 475, 455, $light);
        imageline($canvas, 475, 455, 420, 520, $light);
        imageline($canvas, 420, 520, 350, 520, $main);

        // Twin vertical pins
        imagesetthickness($canvas, 5);
        imageline($canvas, 380, 250, 380, 280, $light);
        imageline($canvas, 415, 250, 415, 280, $light);
        imageline($canvas, 380, 520, 380, 550, $dark);
        imageline($canvas, 415, 520, 415, 550, $dark);
    }

    private function drawEthDiamond($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;
        $cy = 400;

        // Top pyramid points
        $top = [$cx, 160];
        $left = [260, 420];
        $center = [$cx, 480];
        $right = [540, 420];

        // Bottom pyramid tip
        $bottom = [$cx, 640];
        $bottomLeft = [270, 450];
        $bottomCenter = [$cx, 510];
        $bottomRight = [530, 450];

        imagesetthickness($canvas, 4);

        // Top pyramid left facet
        imagepolygon($canvas, [$top[0], $top[1], $left[0], $left[1], $center[0], $center[1]], $dark);
        // Top pyramid right facet
        imagepolygon($canvas, [$top[0], $top[1], $center[0], $center[1], $right[0], $right[1]], $main);

        // Center spine
        imagesetthickness($canvas, 3);
        imageline($canvas, $top[0], $top[1], $center[0], $center[1], $light);

        // Bottom pyramid left facet
        imagepolygon($canvas, [$bottomCenter[0], $bottomCenter[1], $bottomLeft[0], $bottomLeft[1], $bottom[0], $bottom[1]], $dark);
        // Bottom pyramid right facet
        imagepolygon($canvas, [$bottomCenter[0], $bottomCenter[1], $bottom[0], $bottom[1], $bottomRight[0], $bottomRight[1]], $main);

        // Outer framing diamond
        imagesetthickness($canvas, 2);
        imagepolygon($canvas, [$cx, 120, 580, $cy, $cx, 680, 220, $cy], $light);
        imagepolygon($canvas, [$cx, 90, 610, $cy, $cx, 710, 190, $cy], $dark);
    }

    private function drawEthNeon($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;
        $cy = 400;

        // Concentric neon orbit rings
        imagesetthickness($canvas, 3);
        imageellipse($canvas, $cx, $cy, 580, 580, $dark);
        imagesetthickness($canvas, 2);
        imageellipse($canvas, $cx, $cy, 540, 540, $main);
        imagesetthickness($canvas, 1);
        imageellipse($canvas, $cx, $cy, 500, 500, $light);

        // Floating nodes on ring
        for ($i = 0; $i < 12; $i++) {
            $angle = ($i * 30) * M_PI / 180;
            $x = (int) round($cx + cos($angle) * 270);
            $y = (int) round($cy + sin($angle) * 270);
            imagefilledellipse($canvas, $x, $y, ($i % 3 === 0) ? 12 : 6, ($i % 3 === 0) ? 12 : 6, $light);
        }

        // Center Ethereum structure
        $top = [$cx, 200];
        $left = [290, 410];
        $center = [$cx, 460];
        $right = [510, 410];
        $bottom = [$cx, 600];

        imagesetthickness($canvas, 4);
        imageline($canvas, $top[0], $top[1], $left[0], $left[1], $main);
        imageline($canvas, $top[0], $top[1], $right[0], $right[1], $light);
        imageline($canvas, $top[0], $top[1], $center[0], $center[1], $light);
        imageline($canvas, $left[0], $left[1], $center[0], $center[1], $dark);
        imageline($canvas, $right[0], $right[1], $center[0], $center[1], $main);

        imageline($canvas, $left[0], $left[1] + 30, $bottom[0], $bottom[1], $dark);
        imageline($canvas, $right[0], $right[1] + 30, $bottom[0], $bottom[1], $main);
        imageline($canvas, $center[0], $center[1] + 30, $bottom[0], $bottom[1], $light);
    }

    private function drawDoge($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;
        $cy = 400;

        // Medallion coin borders
        imagesetthickness($canvas, 6);
        imageellipse($canvas, $cx, $cy, 560, 560, $main);
        imagesetthickness($canvas, 2);
        imageellipse($canvas, $cx, $cy, 530, 530, $dark);
        imageellipse($canvas, $cx, $cy, 490, 490, $light);

        // Circular stars/studs
        for ($i = 0; $i < 20; $i++) {
            $angle = ($i * 18) * M_PI / 180;
            $x = (int) round($cx + cos($angle) * 255);
            $y = (int) round($cy + sin($angle) * 255);
            imagefilledellipse($canvas, $x, $y, 8, 8, $light);
        }

        // Geometric stylized Doge silhouette (Shiba ears and snout)
        imagesetthickness($canvas, 4);
        // Left ear
        imagepolygon($canvas, [310, 340, 270, 230, 360, 280], $main);
        // Right ear
        imagepolygon($canvas, [490, 340, 530, 230, 440, 280], $light);

        // Head perimeter
        $head = [
            310, 340,
            330, 430,
            380, 490,
            400, 510,
            420, 490,
            470, 430,
            490, 340,
            440, 280,
            360, 280,
        ];
        imagepolygon($canvas, $head, $main);

        // Snout triangle
        imagepolygon($canvas, [380, 430, 420, 430, 400, 470], $dark);
        imagefilledellipse($canvas, 400, 465, 14, 10, $light);

        // Eyes (angled slits)
        imageline($canvas, 345, 360, 375, 370, $light);
        imageline($canvas, 455, 360, 425, 370, $light);
    }

    private function drawMountain($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;
        $cy = 400;

        // Circular frame
        imagesetthickness($canvas, 4);
        imageellipse($canvas, $cx, $cy, 560, 560, $main);
        imagesetthickness($canvas, 2);
        imageellipse($canvas, $cx, $cy, 530, 530, $dark);

        // Sun / Moon in sky
        imageellipse($canvas, 400, 250, 110, 110, $light);
        imagesetthickness($canvas, 1);
        imageellipse($canvas, 400, 250, 140, 140, $dark);

        // Central main peak
        imagesetthickness($canvas, 4);
        $peak1 = [400, 270, 250, 530, 550, 530];
        imagepolygon($canvas, $peak1, $main);

        // Main peak spine & snowcap
        imageline($canvas, 400, 270, 390, 530, $light);
        imageline($canvas, 350, 360, 400, 330, $light);
        imageline($canvas, 400, 330, 460, 370, $light);

        // Secondary peak left
        imagesetthickness($canvas, 3);
        $peak2 = [280, 340, 180, 530, 360, 530];
        imagepolygon($canvas, $peak2, $dark);
        imageline($canvas, 280, 340, 270, 530, $light);

        // Secondary peak right
        $peak3 = [500, 350, 420, 530, 600, 530];
        imagepolygon($canvas, $peak3, $dark);
        imageline($canvas, 500, 350, 510, 530, $light);

        // Horizon & base reflection lines
        imagesetthickness($canvas, 2);
        imageline($canvas, 180, 550, 620, 550, $main);
        imageline($canvas, 220, 570, 580, 570, $dark);
        imageline($canvas, 280, 590, 520, 590, $light);
    }

    private function drawOcean($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;
        $cy = 400;

        // Circular medallion
        imagesetthickness($canvas, 4);
        imageellipse($canvas, $cx, $cy, 560, 560, $main);
        imagesetthickness($canvas, 2);
        imageellipse($canvas, $cx, $cy, 520, 520, $dark);

        // Sweeping waves arcs
        imagesetthickness($canvas, 4);
        imagearc($canvas, 350, 460, 400, 260, 180, 360, $main);
        imagesetthickness($canvas, 3);
        imagearc($canvas, 420, 430, 360, 240, 180, 360, $light);
        imagesetthickness($canvas, 2);
        imagearc($canvas, 300, 500, 460, 280, 180, 360, $dark);

        // Great wave curling crest
        imagearc($canvas, 480, 330, 220, 180, 130, 320, $light);
        imagearc($canvas, 500, 310, 180, 150, 110, 300, $main);

        // Spray droplets
        $droplets = [
            [480, 250], [510, 230], [535, 260], [450, 270],
            [380, 320], [350, 310], [520, 290], [560, 270],
        ];
        foreach ($droplets as [$dx, $dy]) {
            imagefilledellipse($canvas, $dx, $dy, 7, 7, $light);
        }

        // Lower water ripple lines
        imagesetthickness($canvas, 2);
        for ($y = 520; $y <= 580; $y += 20) {
            $span = (int) round(sqrt(max(0, 260 ** 2 - ($y - $cy) ** 2)) * 0.85);
            imageline($canvas, $cx - $span, $y, $cx + $span, $y, $dark);
        }
    }

    private function drawGrid($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;

        // Cyber isometric / perspective grid
        imagesetthickness($canvas, 2);

        // Vanishing point lines
        $vpX = 400;
        $vpY = 220;

        for ($x = 120; $x <= 680; $x += 40) {
            imageline($canvas, $vpX, $vpY, $x, 620, ($x === 400) ? $light : $dark);
        }

        // Horizontal perspective rungs
        $rungs = [250, 285, 330, 385, 450, 530, 620];
        foreach ($rungs as $idx => $y) {
            $ratio = ($y - $vpY) / (620 - $vpY);
            $width = (int) round(280 * $ratio);
            imageline($canvas, $cx - $width, $y, $cx + $width, $y, ($idx % 2 === 0) ? $main : $light);
        }

        // Floating central wireframe cube
        imagesetthickness($canvas, 3);
        $cubeTop = [400, 260, 460, 295, 400, 330, 340, 295];
        imagepolygon($canvas, $cubeTop, $light);
        // Cube front left
        imagepolygon($canvas, [340, 295, 400, 330, 400, 405, 340, 370], $dark);
        // Cube front right
        imagepolygon($canvas, [400, 330, 460, 295, 460, 370, 400, 405], $main);
    }

    private function drawNeon($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;
        $cy = 400;

        // Hexagonal cyber mesh
        $hexes = [
            [$cx, $cy, 70],
            [$cx - 120, $cy - 70, 70],
            [$cx + 120, $cy - 70, 70],
            [$cx - 120, $cy + 70, 70],
            [$cx + 120, $cy + 70, 70],
            [$cx, $cy - 140, 70],
            [$cx, $cy + 140, 70],
        ];

        imagesetthickness($canvas, 3);
        foreach ($hexes as [$hx, $hy, $hr]) {
            $pts = [];
            for ($i = 0; $i < 6; $i++) {
                $angle = ($i * 60) * M_PI / 180;
                $pts[] = (int) round($hx + cos($angle) * $hr);
                $pts[] = (int) round($hy + sin($angle) * $hr);
            }
            imagepolygon($canvas, $pts, $main);
            imagefilledellipse($canvas, $hx, $hy, 10, 10, $light);
        }

        // Outer circular rings
        imagesetthickness($canvas, 2);
        imageellipse($canvas, $cx, $cy, 560, 560, $dark);
        imageellipse($canvas, $cx, $cy, 520, 520, $light);
    }

    private function drawMustang($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;
        $cy = 400;

        // Circular racing crest
        imagesetthickness($canvas, 5);
        imageellipse($canvas, $cx, $cy, 560, 560, $main);
        imagesetthickness($canvas, 2);
        imageellipse($canvas, $cx, $cy, 520, 520, $dark);

        // Three racing stripes across background
        imagesetthickness($canvas, 6);
        imageline($canvas, 200, 200, 200, 600, $dark);
        imageline($canvas, 220, 200, 220, 600, $light);
        imageline($canvas, 240, 200, 240, 600, $main);

        // Galloping horse angular silhouette
        imagesetthickness($canvas, 4);
        $horse = [
            // Head & muzzle
            280, 360,
            330, 330,
            380, 335,
            // Ears
            370, 305,
            395, 325,
            // Mane & neck
            430, 360,
            480, 380,
            // Back & tail
            540, 385,
            570, 420,
            530, 430,
            // Hind leg
            490, 470,
            460, 440,
            // Belly & forelegs
            410, 450,
            370, 490,
            350, 440,
            310, 400,
        ];
        imagepolygon($canvas, $horse, $main);

        // Internal muscle facet line
        imageline($canvas, 380, 335, 420, 410, $light);
        imageline($canvas, 420, 410, 480, 380, $light);
    }

    private function drawLambo($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;
        $cy = 400;

        // Bull crest shield outline
        $shield = [
            $cx, 160,
            570, 220,
            530, 500,
            $cx, 650,
            270, 500,
            230, 220,
        ];
        imagesetthickness($canvas, 5);
        imagepolygon($canvas, $shield, $main);

        // Inner shield border
        $innerShield = [
            $cx, 190,
            540, 245,
            505, 485,
            $cx, 615,
            295, 485,
            260, 245,
        ];
        imagesetthickness($canvas, 2);
        imagepolygon($canvas, $innerShield, $dark);

        // Aggressive geometric bull silhouette
        imagesetthickness($canvas, 4);
        // Left horn
        imageline($canvas, 370, 320, 310, 260, $light);
        imageline($canvas, 310, 260, 345, 290, $main);
        // Right horn
        imageline($canvas, 430, 320, 490, 260, $light);
        imageline($canvas, 490, 260, 455, 290, $main);

        // Head plate
        $bullHead = [
            370, 320,
            430, 320,
            440, 380,
            415, 440,
            385, 440,
            360, 380,
        ];
        imagepolygon($canvas, $bullHead, $main);

        // Angular faceted snout
        imageline($canvas, 385, 440, 400, 465, $light);
        imageline($canvas, 415, 440, 400, 465, $light);

        // Muscular shoulder ridges
        imageline($canvas, 330, 380, 290, 420, $dark);
        imageline($canvas, 470, 380, 510, 420, $dark);
    }

    private function drawPorsche($canvas, int $main, int $light, int $dark): void
    {
        $cx = 400;
        $cy = 400;

        // Circular technical bezel
        imagesetthickness($canvas, 4);
        imageellipse($canvas, $cx, $cy, 560, 560, $main);
        imagesetthickness($canvas, 2);
        imageellipse($canvas, $cx, $cy, 530, 530, $dark);

        // Classic 911 aerodynamic roofline curve
        imagesetthickness($canvas, 5);
        $car = [
            // Front bumper & hood
            210, 440,
            240, 400,
            280, 380,
            330, 360,
            // Windshield & roof
            390, 305,
            450, 305,
            // Ducktail spoiler & rear
            520, 360,
            570, 390,
            590, 430,
            560, 440,
        ];
        for ($i = 0; $i < count($car) - 2; $i += 2) {
            imageline($canvas, $car[$i], $car[$i + 1], $car[$i + 2], $car[$i + 3], $main);
        }

        // Front & rear wheel arches & wheels
        imagesetthickness($canvas, 4);
        imagearc($canvas, 290, 440, 90, 90, 180, 360, $light);
        imageellipse($canvas, 290, 440, 70, 70, $dark);
        imagearc($canvas, 510, 440, 90, 90, 180, 360, $light);
        imageellipse($canvas, 510, 440, 70, 70, $dark);

        // Ground baseline
        imagesetthickness($canvas, 2);
        imageline($canvas, 180, 475, 620, 475, $dark);

        // Window outline
        imagesetthickness($canvas, 2);
        imagepolygon($canvas, [345, 360, 395, 315, 445, 315, 495, 360], $light);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThemeAssetController extends Controller
{
    public function serve(Request $request, ThemeManager $themes, string $theme, string $path): Response|BinaryFileResponse
    {
        $filePath = $themes->getAssetPath($theme, $path);

        if (!$filePath) {
            abort(404);
        }

        // Detect MIME type
        $ext  = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'css'         => 'text/css',
            'js'          => 'application/javascript',
            'png'         => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'svg'         => 'image/svg+xml',
            'webp'        => 'image/webp',
            'woff'        => 'font/woff',
            'woff2'       => 'font/woff2',
            'ttf'         => 'font/ttf',
            'ico'         => 'image/x-icon',
            default       => 'application/octet-stream',
        };

        return response()->file($filePath, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}

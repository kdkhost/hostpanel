<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class PwaController extends Controller
{
    public function manifest()
    {
        $name = Setting::get('app.name', config('app.name', 'HostPanel'));
        $color = Setting::get('app.theme_color', '#1a56db');

        $manifest = [
            'name'             => $name,
            'short_name'       => Setting::get('app.short_name', 'HostPanel'),
            'description'      => Setting::get('app.description', 'Painel de hospedagem completo'),
            'start_url'        => '/cliente/dashboard',
            'display'          => 'standalone',
            'background_color' => '#ffffff',
            'theme_color'      => $color,
            'orientation'      => 'portrait',
            'scope'            => '/',
            'lang'             => 'pt-BR',
            'icons'            => [
                ['src' => asset('images/icons/icon-72x72.png'),   'sizes' => '72x72',   'type' => 'image/png'],
                ['src' => asset('images/icons/icon-96x96.png'),   'sizes' => '96x96',   'type' => 'image/png'],
                ['src' => asset('images/icons/icon-128x128.png'), 'sizes' => '128x128', 'type' => 'image/png'],
                ['src' => asset('images/icons/icon-144x144.png'), 'sizes' => '144x144', 'type' => 'image/png'],
                ['src' => asset('images/icons/icon-152x152.png'), 'sizes' => '152x152', 'type' => 'image/png'],
                ['src' => asset('images/icons/icon-192x192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => asset('images/icons/icon-384x384.png'), 'sizes' => '384x384', 'type' => 'image/png'],
                ['src' => asset('images/icons/icon-512x512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ],
            'categories'  => ['business', 'productivity'],
            'screenshots' => [],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function serviceWorker()
    {
        $content = view('pwa.service-worker')->render();
        return response($content)->header('Content-Type', 'application/javascript');
    }
}

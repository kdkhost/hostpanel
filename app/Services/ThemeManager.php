<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ThemeManager
{
    private string $active;
    private string $themesPath;

    public function __construct()
    {
        $this->themesPath = resource_path('themes');
        $this->active     = Setting::get('active_theme', 'default');
    }

    /* ------------------------------------------------------------------ */
    /*  Boot: prepend theme views to Laravel's view finder                 */
    /* ------------------------------------------------------------------ */

    public function boot(): void
    {
        $viewPath = $this->getViewPath($this->active);

        if ($viewPath && is_dir($viewPath)) {
            app('view.finder')->prependLocation($viewPath);
        }

        // Compartilha o tema ativo com todas as views
        view()->share('activeTheme', $this->active);
        view()->share('themeManifest', $this->getManifest($this->active));
    }

    /* ------------------------------------------------------------------ */
    /*  Theme discovery                                                     */
    /* ------------------------------------------------------------------ */

    /**
     * Lista todos os temas disponíveis com seus manifestos.
     */
    public function all(): array
    {
        return Cache::remember('themes.list', 300, function () {
            $themes = [];

            if (!is_dir($this->themesPath)) {
                return [['id' => 'default', 'name' => 'Padrão', 'active' => true]];
            }

            foreach (File::directories($this->themesPath) as $dir) {
                $id       = basename($dir);
                $manifest = $this->getManifest($id);

                $themes[] = array_merge([
                    'id'          => $id,
                    'name'        => ucfirst($id),
                    'description' => '',
                    'author'      => '',
                    'version'     => '1.0.0',
                    'preview'     => null,
                    'supports'    => ['store', 'client', 'admin'],
                ], $manifest, [
                    'id'     => $id,
                    'active' => $id === $this->active,
                    'path'   => $dir,
                ]);
            }

            // Garante que 'default' sempre aparece (usa views base sem pasta)
            $hasDefault = collect($themes)->pluck('id')->contains('default');
            if (!$hasDefault) {
                array_unshift($themes, [
                    'id'          => 'default',
                    'name'        => 'Padrão (Sistema)',
                    'description' => 'Tema padrão do sistema sem customizações.',
                    'author'      => 'HostPanel',
                    'version'     => '1.0.0',
                    'preview'     => null,
                    'supports'    => ['store', 'client', 'admin'],
                    'active'      => $this->active === 'default',
                    'path'        => null,
                ]);
            }

            return $themes;
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Getters                                                             */
    /* ------------------------------------------------------------------ */

    public function getActive(): string
    {
        return $this->active;
    }

    public function getManifest(string $themeId): array
    {
        $json = $this->themesPath . "/{$themeId}/theme.json";

        if (!file_exists($json)) {
            return [];
        }

        return json_decode(file_get_contents($json), true) ?? [];
    }

    public function getViewPath(string $themeId): ?string
    {
        if ($themeId === 'default') return null;

        $path = $this->themesPath . "/{$themeId}/views";
        return is_dir($path) ? $path : null;
    }

    public function getAssetPath(string $themeId, string $file): ?string
    {
        $path = $this->themesPath . "/{$themeId}/assets/" . ltrim($file, '/');
        return file_exists($path) ? $path : null;
    }

    public function assetUrl(string $file): string
    {
        if ($this->active === 'default') {
            return asset($file);
        }

        return route('theme.asset', [
            'theme' => $this->active,
            'path'  => ltrim($file, '/'),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Activation                                                          */
    /* ------------------------------------------------------------------ */

    public function activate(string $themeId): void
    {
        if ($themeId !== 'default') {
            $path = $this->themesPath . "/{$themeId}";
            if (!is_dir($path)) {
                throw new \RuntimeException("Tema '{$themeId}' não encontrado.");
            }
        }

        Setting::set('active_theme', $themeId, 'appearance');
        Cache::forget('settings.all');
        Cache::forget('themes.list');

        $this->active = $themeId;
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    public function exists(string $themeId): bool
    {
        return $themeId === 'default' || is_dir($this->themesPath . "/{$themeId}");
    }

    public function clearCache(): void
    {
        Cache::forget('themes.list');
        Cache::forget('settings.all');
    }
}

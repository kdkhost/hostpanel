<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ThemeManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function __construct(private ThemeManager $themes) {}

    /**
     * Página de gerenciamento de temas.
     * Rota: GET /admin/configuracoes/temas
     */
    public function index()
    {
        $themes = $this->themes->all();
        $active = $this->themes->getActive();

        return view('admin.themes.index', compact('themes', 'active'));
    }

    /**
     * Ativa um tema.
     * Rota: POST /admin/configuracoes/temas/{theme}/ativar
     */
    public function activate(Request $request, string $theme): JsonResponse
    {
        try {
            $this->themes->activate($theme);

            return response()->json([
                'success' => true,
                'message' => "Tema '{$theme}' ativado com sucesso! Recarregue a página para ver as mudanças.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Retorna os detalhes de um tema.
     * Rota: GET /admin/configuracoes/temas/{theme}
     */
    public function show(string $theme): JsonResponse
    {
        $all      = $this->themes->all();
        $manifest = collect($all)->firstWhere('id', $theme);

        if (!$manifest) {
            return response()->json(['message' => 'Tema não encontrado.'], 404);
        }

        return response()->json($manifest);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CmsController extends Controller
{
    public function pages(Request $request)
    {
        if (!$request->expectsJson()) {
            return view('admin.cms.index');
        }

        return response()->json(Page::orderBy('sort_order')->get());
    }

    public function storePage(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $page = Page::create(array_merge($request->all(), [
            'slug' => Str::slug($request->title),
        ]));

        return response()->json(['message' => 'Pagina criada!', 'page' => $page], 201);
    }

    public function updatePage(Request $request, Page $page): JsonResponse
    {
        $page->update($request->all());

        return response()->json(['message' => 'Pagina atualizada!', 'page' => $page->fresh()]);
    }

    public function banners(): JsonResponse
    {
        $banners = Banner::orderBy('sort_order')
            ->get()
            ->map(fn (Banner $banner) => $this->bannerResponse($banner))
            ->values();

        return response()->json($banners);
    }

    public function storeBanner(Request $request): JsonResponse
    {
        $banner = Banner::create($this->bannerPayload($request));

        return response()->json([
            'message' => 'Banner criado!',
            'banner' => $this->bannerResponse($banner->fresh()),
        ], 201);
    }

    public function updateBanner(Request $request, Banner $banner): JsonResponse
    {
        $banner->update($this->bannerPayload($request, $banner));

        return response()->json([
            'message' => 'Banner atualizado!',
            'banner' => $this->bannerResponse($banner->fresh()),
        ]);
    }

    public function faqs(): JsonResponse
    {
        return response()->json(Faq::orderBy('sort_order')->get());
    }

    public function storeFaq(Request $request): JsonResponse
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq = Faq::create($request->all());

        return response()->json(['message' => 'FAQ criado!', 'faq' => $faq], 201);
    }

    public function announcements(): JsonResponse
    {
        return response()->json(Announcement::orderByDesc('created_at')->get());
    }

    public function storeAnnouncement(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        $announcement = Announcement::create($request->only(['title', 'content', 'type', 'published']));

        return response()->json(['message' => 'Anuncio criado!', 'announcement' => $announcement], 201);
    }

    public function destroyPage(Page $page): JsonResponse
    {
        $page->delete();

        return response()->json(['message' => 'Pagina excluida!']);
    }

    public function destroyBanner(Banner $banner): JsonResponse
    {
        $this->deleteStoredBannerImage($banner->image);
        $banner->delete();

        return response()->json(['message' => 'Banner excluido!']);
    }

    public function destroyFaq(Faq $faq): JsonResponse
    {
        $faq->delete();

        return response()->json(['message' => 'FAQ excluido!']);
    }

    public function destroyAnnouncement(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return response()->json(['message' => 'Anuncio excluido!']);
    }

    public function updateFaq(Request $request, Faq $faq): JsonResponse
    {
        $faq->update($request->all());

        return response()->json(['message' => 'FAQ atualizado!', 'faq' => $faq->fresh()]);
    }

    public function updateAnnouncement(Request $request, Announcement $announcement): JsonResponse
    {
        $announcement->update($request->all());

        return response()->json(['message' => 'Anuncio atualizado!', 'announcement' => $announcement->fresh()]);
    }

    protected function bannerPayload(Request $request, ?Banner $banner = null): array
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string',
            'link' => 'nullable|string|max:255',
            'cta_url' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'position' => 'nullable|string|max:50',
            'target' => 'nullable|string|max:20',
            'image' => 'nullable|string|max:2048',
            'image_file' => 'nullable|image|max:10240',
        ]);

        $imagePath = $banner?->image;

        if ($request->hasFile('image_file')) {
            $this->deleteStoredBannerImage($banner?->image);
            $imagePath = $request->file('image_file')->store('cms/banners', 'public');
        } elseif ($request->filled('image')) {
            $imagePath = trim((string) $request->input('image'));
        }

        if (!$imagePath) {
            throw ValidationException::withMessages([
                'image_file' => 'Envie uma imagem para o banner.',
            ]);
        }

        $title = trim((string) ($request->input('title') ?: $banner?->title ?: $banner?->name ?: ''));

        return [
            'name' => $request->input('name') ?: ($title !== '' ? $title : 'Banner ' . now()->format('d/m/Y H:i')),
            'title' => $title !== '' ? $title : null,
            'subtitle' => $request->input('subtitle'),
            'image' => $imagePath,
            'cta_url' => $request->input('cta_url') ?: $request->input('link'),
            'cta_label' => $request->input('cta_label') ?: null,
            'target' => $request->input('target', $banner?->target ?? '_self'),
            'position' => $request->input('position', $banner?->position ?? 'home_hero'),
            'active' => $request->boolean('active', $banner?->active ?? true),
            'sort_order' => (int) $request->input('sort_order', $banner?->sort_order ?? 0),
        ];
    }

    protected function bannerResponse(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'name' => $banner->name,
            'title' => $banner->title ?: $banner->name,
            'subtitle' => $banner->subtitle,
            'image' => $banner->image,
            'image_url' => $banner->image_url,
            'link' => $banner->cta_url,
            'cta_url' => $banner->cta_url,
            'cta_label' => $banner->cta_label,
            'active' => (bool) $banner->active,
            'sort_order' => (int) $banner->sort_order,
            'position' => $banner->position,
            'target' => $banner->target,
        ];
    }

    protected function deleteStoredBannerImage(?string $path): void
    {
        if (!$path) {
            return;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk('public')->delete(ltrim($path, '/'));
    }
}

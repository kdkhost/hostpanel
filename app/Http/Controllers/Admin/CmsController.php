<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CmsController extends Controller
{
    public function pages(Request $request)
    {
        if (!$request->expectsJson()) return view('admin.cms.index');
        return response()->json(Page::orderBy('sort_order')->get());
    }

    public function storePage(Request $request): JsonResponse
    {
        $request->validate(['title' => 'required|string|max:255', 'content' => 'required|string']);
        $page = Page::create(array_merge($request->all(), ['slug' => Str::slug($request->title)]));
        return response()->json(['message' => 'Página criada!', 'page' => $page], 201);
    }

    public function updatePage(Request $request, Page $page): JsonResponse
    {
        $page->update($request->all());
        return response()->json(['message' => 'Página atualizada!', 'page' => $page->fresh()]);
    }

    public function banners(): JsonResponse
    {
        return response()->json(\App\Models\Banner::orderBy('sort_order')->get());
    }

    public function storeBanner(Request $request): JsonResponse
    {
        $request->validate(['title' => 'required|string', 'image' => 'required|string']);
        $banner = \App\Models\Banner::create($request->all());
        return response()->json(['message' => 'Banner criado!', 'banner' => $banner], 201);
    }

    public function faqs(): JsonResponse
    {
        return response()->json(\App\Models\Faq::orderBy('sort_order')->get());
    }

    public function storeFaq(Request $request): JsonResponse
    {
        $request->validate(['question' => 'required|string', 'answer' => 'required|string']);
        $faq = \App\Models\Faq::create($request->all());
        return response()->json(['message' => 'FAQ criado!', 'faq' => $faq], 201);
    }

    public function announcements(): JsonResponse
    {
        return response()->json(Announcement::orderByDesc('created_at')->get());
    }

    public function storeAnnouncement(Request $request): JsonResponse
    {
        $request->validate(['title' => 'required|string', 'content' => 'required|string']);
        $ann = Announcement::create(array_merge($request->all(), ['slug' => Str::slug($request->title . '-' . now()->timestamp)]));
        return response()->json(['message' => 'Anúncio criado!', 'announcement' => $ann], 201);
    }

    public function destroyPage(Page $page): JsonResponse
    {
        $page->delete();
        return response()->json(['message' => 'Página excluída!']);
    }

    public function destroyBanner(\App\Models\Banner $banner): JsonResponse
    {
        $banner->delete();
        return response()->json(['message' => 'Banner excluído!']);
    }

    public function destroyFaq(\App\Models\Faq $faq): JsonResponse
    {
        $faq->delete();
        return response()->json(['message' => 'FAQ excluído!']);
    }

    public function destroyAnnouncement(Announcement $announcement): JsonResponse
    {
        $announcement->delete();
        return response()->json(['message' => 'Anúncio excluído!']);
    }

    public function updateBanner(Request $request, \App\Models\Banner $banner): JsonResponse
    {
        $banner->update($request->all());
        return response()->json(['message' => 'Banner atualizado!', 'banner' => $banner->fresh()]);
    }

    public function updateFaq(Request $request, \App\Models\Faq $faq): JsonResponse
    {
        $faq->update($request->all());
        return response()->json(['message' => 'FAQ atualizado!', 'faq' => $faq->fresh()]);
    }

    public function updateAnnouncement(Request $request, Announcement $announcement): JsonResponse
    {
        $announcement->update($request->all());
        return response()->json(['message' => 'Anúncio atualizado!', 'announcement' => $announcement->fresh()]);
    }
}

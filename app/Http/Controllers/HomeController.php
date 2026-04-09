<?php

namespace App\Http\Controllers;

use App\Models\ProductGroup;
use App\Models\DomainTld;
use App\Models\Announcement;
use App\Models\KnowledgeBase;
use App\Models\Page;
use App\Models\Setting;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use App\Mail\ContactFormMail;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Track affiliate referral
        if ($ref = $request->query('ref')) {
            app(AffiliateService::class)->trackVisit($ref, $request->ip(), $request->fullUrl());
        }

        $groups = ProductGroup::with(['products' => fn($q) =>
            $q->where('active', true)->where('hidden', false)->where('featured', true)
              ->with(['pricing' => fn($p) => $p->where('active', true)->orderBy('price')])
              ->limit(3)
        ])->where('active', true)->orderBy('sort_order')->get();

        $announcements = Announcement::where('published', true)
            ->orderByDesc('created_at')->limit(3)->get();

        return view('home.index', compact('groups', 'announcements'));
    }

    public function plans()
    {
        $groups = ProductGroup::with(['products' => fn($q) =>
            $q->where('active', true)->where('hidden', false)
              ->with(['pricing' => fn($p) => $p->where('active', true)->orderBy('billing_cycle')])
              ->orderBy('sort_order')
        ])->where('active', true)->orderBy('sort_order')->get();

        return view('home.plans', compact('groups'));
    }

    public function planDetail(string $slug)
    {
        $product = \App\Models\Product::where('slug', $slug)->where('active', true)->firstOrFail();
        $product->load(['pricing' => fn($q) => $q->where('active', true)->orderBy('price'), 'group']);
        return view('home.plan-detail', compact('product'));
    }

    public function domainSearch(Request $request)
    {
        $tlds = DomainTld::where('active', true)->orderBy('sort_order')->get();
        $domain = $request->query('dominio');
        return view('home.domain-search', compact('tlds', 'domain'));
    }

    public function page(string $slug)
    {
        $page = Page::where('slug', $slug)->where('published', true)->firstOrFail();
        return view('home.page', compact('page'));
    }

    public function knowledgeBase(Request $request)
    {
        $articles = KnowledgeBase::where('published', true)
            ->when($request->q, fn($q) =>
                $q->where('title', 'like', "%{$request->q}%")
                  ->orWhere('content', 'like', "%{$request->q}%")
            )->orderByDesc('views')->paginate(15);

        return view('home.knowledge-base', compact('articles'));
    }

    public function kbArticle(string $slug)
    {
        $article = KnowledgeBase::where('slug', $slug)->where('published', true)->firstOrFail();
        $article->increment('views');
        return view('home.kb-article', compact('article'));
    }

    public function announcements()
    {
        $announcements = Announcement::where('published', true)
            ->orderByDesc('created_at')->paginate(10);
        return view('home.announcements', compact('announcements'));
    }

    public function store(Request $request)
    {
        $groups = ProductGroup::with(['products' => fn($q) =>
            $q->where('active', true)->where('hidden', false)
              ->with(['pricing' => fn($p) => $p->where('active', true)->orderBy('price')->limit(1)])
              ->orderBy('sort_order')
        ])->where('active', true)->orderBy('sort_order')->get();

        $activeGroup = $request->query('categoria');
        return view('home.store', compact('groups', 'activeGroup'));
    }

    public function orderProduct(string $slug, Request $request)
    {
        $product = \App\Models\Product::where('slug', $slug)
            ->where('active', true)->where('hidden', false)->firstOrFail();
        $product->load(['pricing' => fn($q) => $q->where('active', true)->orderBy('price'), 'group', 'features']);
        $tlds = DomainTld::where('active', true)->orderBy('sort_order')->limit(20)->get();
        $selectedCycle = $request->query('ciclo', $product->pricing->first()?->billing_cycle ?? 'monthly');
        return view('home.order-product', compact('product', 'tlds', 'selectedCycle'));
    }

    public function cart(Request $request)
    {
        return view('home.cart');
    }

    public function checkDomain(Request $request)
    {
        $domain = strtolower(trim($request->query('domain', '')));

        if (!$domain) {
            return response()->json(['available' => false, 'message' => 'Domínio inválido.'], 422);
        }

        // Extrair TLD e verificar se está ativo
        $parts = explode('.', $domain, 2);
        $tld   = $parts[1] ?? '';

        $tldModel = DomainTld::where('tld', $tld)->where('active', true)->first();

        if (!$tldModel) {
            return response()->json(['available' => false, 'message' => 'TLD não suportado.']);
        }

        // Verificação real via checkdomain (stub — retorna disponível para fins de demo)
        // Em produção, integrar com API da registradora (ex: Registro.br, GoDaddy, ResellerClub)
        $available = true;

        return response()->json([
            'available' => $available,
            'domain'    => $domain,
            'tld_price' => $tldModel->register_price ?? null,
        ]);
    }

    public function contact()
    {
        return view('home.contact');
    }

    public function contactSubmit(Request $request)
    {
        // Validar reCAPTCHA v3 se configurado
        if (config('services.recaptcha.secret_key')) {
            $token = $request->input('recaptcha_token');

            if (!$token) {
                return back()->withErrors(['recaptcha' => 'Verificação de segurança necessária.'])->withInput();
            }

            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => config('services.recaptcha.secret_key'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            $result = $response->json();
            $scoreThreshold = config('services.recaptcha.score_threshold', 0.5);

            if (!$result['success'] || ($result['score'] ?? 0) < $scoreThreshold) {
                return back()->withErrors(['recaptcha' => 'Falha na verificação de segurança. Tente novamente.'])->withInput();
            }
        }

        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'required|email|max:100',
            'subject' => 'required|string|max:150',
            'message' => 'required|string|max:2000',
            'phone'   => 'nullable|string|max:20',
        ]);

        // Aqui você pode enviar email ou criar ticket automaticamente
        // Por enquanto, vamos apenas retornar sucesso
        // Mail::to(config('mail.from.address'))->send(new ContactFormMail($validated));

        return back()->with('success', 'Mensagem enviada com sucesso! Entraremos em contato em breve.');
    }
}

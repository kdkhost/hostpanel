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
        // Validar reCAPTCHA v3 se habilitado no painel admin
        $recaptchaEnabled = Setting::get('recaptcha.enabled', false);
        $recaptchaSecret = Setting::get('recaptcha.secret_key', '');

        if ($recaptchaEnabled && $recaptchaSecret) {
            $token = $request->input('recaptcha_token');

            if (!$token) {
                return back()->withErrors(['recaptcha' => 'Verificação de segurança necessária.'])->withInput();
            }

            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => $recaptchaSecret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            $result = $response->json();
            $scoreThreshold = (float) Setting::get('recaptcha.score_threshold', 0.5);

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

    /**
     * Checkout público - Fluxo WHMCS style
     * Aceita itens via query params ou session
     */
    public function checkout(Request $request)
    {
        // Itens podem vir por query param (ex: ?items=[{"product_id":1,...}]) ou session
        $items = [];
        if ($request->has('items')) {
            $items = json_decode($request->query('items'), true) ?? [];
        } elseif (session()->has('cart_items')) {
            $items = session('cart_items', []);
        }

        // Validar e carregar produtos
        $cartItems = [];
        $total = 0;
        $hasDomainRequirement = false;

        foreach ($items as $item) {
            $product = \App\Models\Product::where('id', $item['product_id'] ?? null)
                ->where('active', true)
                ->with(['pricing' => fn($q) => $q->where('billing_cycle', $item['cycle'] ?? 'monthly')])
                ->first();

            if (!$product) continue;

            $pricing = $product->pricing->first();
            $price = $pricing?->price ?? 0;
            $setupFee = $pricing?->setup_fee ?? 0;
            $itemTotal = $price + $setupFee;

            // Verificar se produto exige domínio
            $requiresDomain = $product->requires_domain ?? false;
            if ($requiresDomain && empty($item['domain'])) {
                $hasDomainRequirement = true;
            }

            $cartItems[] = [
                'product' => $product,
                'cycle' => $item['cycle'] ?? 'monthly',
                'domain' => $item['domain'] ?? '',
                'price' => $price,
                'setup_fee' => $setupFee,
                'total' => $itemTotal,
                'requires_domain' => $requiresDomain,
            ];

            $total += $itemTotal;
        }

        // Gateways de pagamento ativos
        $gateways = \App\Models\PaymentGateway::where('active', true)->get();

        // Se cliente logado, pega dados
        $client = auth('client')->user();

        return view('home.checkout', compact('cartItems', 'total', 'gateways', 'client', 'hasDomainRequirement'));
    }

    /**
     * Processar checkout público (criar conta + pedido ou só pedido)
     */
    public function checkoutSubmit(Request $request)
    {
        $isLoggedIn = auth('client')->check();

        $rules = [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.billing_cycle' => 'required|string',
            'items.*.domain' => 'nullable|string|max:255',
            'payment_method' => 'required|string',
            'coupon_code' => 'nullable|string|max:50',
        ];

        // Se não logado, validar dados de criação de conta
        if (!$isLoggedIn) {
            $rules['account_type'] = 'required|in:existing,new';
            $rules['email'] = 'required|email|max:100';
            $rules['password'] = 'required_if:account_type,new|string|min:6|max:100';
            $rules['first_name'] = 'required_if:account_type,new|string|max:50';
            $rules['last_name'] = 'required_if:account_type,new|string|max:50';
            $rules['phone'] = 'nullable|string|max:20';
            $rules['country'] = 'nullable|string|max:2';
        }

        $validated = $request->validate($rules);

        try {
            $client = null;

            // Lidar com autenticação/criação de conta
            if (!$isLoggedIn) {
                if ($validated['account_type'] === 'existing') {
                    // Tentar login
                    $credentials = ['email' => $validated['email'], 'password' => $request->password];
                    if (!auth('client')->attempt($credentials)) {
                        return back()->withErrors(['email' => 'Credenciais inválidas.'])->withInput();
                    }
                    $client = auth('client')->user();
                } else {
                    // Criar nova conta
                    $existingClient = \App\Models\Client::where('email', $validated['email'])->first();
                    if ($existingClient) {
                        return back()->withErrors(['email' => 'Este email já está cadastrado. Faça login.'])->withInput();
                    }

                    $client = \App\Models\Client::create([
                        'email' => $validated['email'],
                        'password' => bcrypt($validated['password']),
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                        'phone' => $validated['phone'] ?? null,
                        'country' => $validated['country'] ?? 'BR',
                        'status' => 'active',
                    ]);

                    auth('client')->login($client);
                }
            } else {
                $client = auth('client')->user();
            }

            // Criar pedido via OrderService
            $mappedItems = collect($validated['items'])->map(fn($item) => [
                'product_id' => $item['product_id'],
                'billing_cycle' => $item['billing_cycle'],
                'domain' => $item['domain'] ?? null,
            ])->toArray();

            $order = app(\App\Services\OrderService::class)->create(
                $client,
                $mappedItems,
                $validated['coupon_code'] ?? null,
                $validated['payment_method']
            );

            // Limpar carrinho
            session()->forget('cart_items');

            return redirect()->route('client.orders.show', $order->id)
                ->with('success', 'Pedido realizado com sucesso!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}

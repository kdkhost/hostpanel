<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    protected function productPayload(Product $product): array
    {
        $prices = $product->pricing
            ->sortBy('billing_cycle')
            ->mapWithKeys(fn (ProductPricing $price) => [
                $price->billing_cycle => (string) $price->price,
            ])
            ->all();

        return [
            'id' => $product->id,
            'product_group_id' => $product->product_group_id,
            'server_group_id' => $product->server_group_id,
            'name' => $product->name,
            'tagline' => $product->getAttribute('tagline'),
            'slug' => $product->slug,
            'description' => $product->description,
            'type' => $product->type,
            'module' => $product->module,
            'billing_cycle_type' => $product->billing_cycle_type,
            'features' => $product->features ?? [],
            'featured' => (bool) $product->featured,
            'hidden' => (bool) $product->hidden,
            'active' => (bool) $product->active,
            'sort_order' => $product->sort_order,
            'services_count' => $product->services_count,
            'group' => $product->group?->only(['id', 'name']),
            'prices' => $prices,
            'pricing' => $product->pricing->map(fn (ProductPricing $price) => [
                'billing_cycle' => $price->billing_cycle,
                'currency' => $price->currency,
                'price' => (string) $price->price,
                'setup_fee' => (string) $price->setup_fee,
                'active' => (bool) $price->active,
            ])->values()->all(),
        ];
    }

    protected function baseRules(): array
    {
        return [
            'product_group_id' => 'nullable|exists:product_groups,id',
            'server_group_id' => 'nullable|exists:server_groups,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'welcome_email' => 'nullable|string',
            'type' => 'required|in:shared,hosting,reseller,vps,dedicated,domain,addon,other',
            'module' => 'nullable|string|max:50',
            'billing_cycle_type' => 'nullable|in:one_time,recurring',
            'require_domain' => 'nullable|boolean',
            'auto_setup' => 'nullable|boolean',
            'auto_setup_type' => 'nullable|in:payment,order,manual',
            'cpanel_pkg' => 'nullable|string|max:255',
            'disk_space' => 'nullable|integer|min:0',
            'bandwidth' => 'nullable|integer|min:0',
            'subdomains' => 'nullable|integer|min:0',
            'email_accounts' => 'nullable|integer|min:0',
            'databases' => 'nullable|integer|min:0',
            'ftp_accounts' => 'nullable|integer|min:0',
            'ssl_free' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
            'hidden' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'active' => 'nullable|boolean',
            'configurable_options' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            'features' => 'nullable|array',
            'features.*' => 'nullable|string|max:255',
        ];
    }

    protected function pricingRules(bool $required = false): array
    {
        $pricingRule = $required ? 'required|array|min:1' : 'sometimes|array|min:1';

        return [
            'pricing' => $pricingRule,
            'pricing.*.billing_cycle' => 'required_with:pricing|string|in:one_time,monthly,quarterly,semiannually,annually,biennially,triennially',
            'pricing.*.currency' => 'nullable|string|size:3',
            'pricing.*.price' => 'required_with:pricing|numeric|min:0',
            'pricing.*.setup_fee' => 'nullable|numeric|min:0',
            'pricing.*.active' => 'nullable|boolean',
        ];
    }

    protected function normalizedData(Request $request): array
    {
        $data = $request->only([
            'product_group_id', 'server_group_id', 'name', 'description', 'welcome_email',
            'type', 'module', 'billing_cycle_type', 'require_domain', 'auto_setup', 'auto_setup_type',
            'cpanel_pkg', 'disk_space', 'bandwidth', 'subdomains', 'email_accounts', 'databases',
            'ftp_accounts', 'ssl_free', 'featured', 'hidden', 'sort_order', 'active',
            'configurable_options', 'custom_fields', 'features',
        ]);

        $data['module'] = strtolower(trim((string) ($data['module'] ?? 'none'))) ?: 'none';
        $data['billing_cycle_type'] = $data['billing_cycle_type'] ?? 'recurring';

        // Generate unique slug
        $baseSlug = Str::slug($request->input('name'));
        $slug = $baseSlug;
        $i = 1;
        while (Product::withTrashed()->where('slug', $slug)->where('id', '!=', $request->route('product')?->id ?? 0)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }
        $data['slug'] = $slug;

        return $data;
    }

    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $products = Product::with(['group:id,name', 'pricing'])
                ->withCount('services')
                ->when($request->search, fn ($query) => $query->where('name', 'like', "%{$request->search}%"))
                ->when($request->filled('group_id'), fn ($query) => $query->where('product_group_id', $request->integer('group_id')))
                ->when($request->type, fn ($query) => $query->where('type', $request->type))
                ->when(!is_null($request->active), fn ($query) => $query->where('active', $request->boolean('active')))
                ->orderBy($request->sort_by ?? 'sort_order', $request->sort_dir ?? 'asc')
                ->paginate($request->integer('per_page', 20))
                ->through(fn (Product $product) => $this->productPayload($product));

            return response()->json($products);
        }

        $groups = ProductGroup::orderBy('sort_order')->get();

        return view('admin.products.index', compact('groups'));
    }

    public function show(Product $product)
    {
        $product->load(['group', 'serverGroup', 'allPricing']);

        return view('admin.products.show', compact('product'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(array_merge($this->baseRules(), $this->pricingRules(true)));

        $product = Product::create($this->normalizedData($request));

        foreach ($request->input('pricing', []) as $price) {
            ProductPricing::create([
                'product_id' => $product->id,
                'currency' => $price['currency'] ?? 'BRL',
                'billing_cycle' => $price['billing_cycle'],
                'price' => $price['price'],
                'setup_fee' => $price['setup_fee'] ?? 0,
                'active' => $price['active'] ?? true,
            ]);
        }

        $product->load(['group:id,name', 'pricing'])->loadCount('services');

        return response()->json([
            'message' => 'Produto criado com sucesso!',
            'product' => $this->productPayload($product),
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $request->validate(array_merge($this->baseRules(), $this->pricingRules($request->has('pricing'))));

        $product->update($this->normalizedData($request));

        if ($request->has('pricing')) {
            foreach ($request->input('pricing', []) as $price) {
                ProductPricing::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'currency' => $price['currency'] ?? 'BRL',
                        'billing_cycle' => $price['billing_cycle'],
                    ],
                    [
                        'price' => $price['price'],
                        'setup_fee' => $price['setup_fee'] ?? 0,
                        'active' => $price['active'] ?? true,
                    ]
                );
            }
        }

        $product = $product->fresh(['group:id,name', 'pricing'])->loadCount('services');

        return response()->json([
            'message' => 'Produto atualizado!',
            'product' => $this->productPayload($product),
        ]);
    }

    public function toggleStatus(Product $product): JsonResponse
    {
        $product->update([
            'active' => !$product->active,
        ]);

        return response()->json([
            'message' => $product->active ? 'Produto ativado com sucesso!' : 'Produto desativado com sucesso!',
            'active' => (bool) $product->active,
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->services()->where('status', 'active')->exists()) {
            return response()->json(['message' => 'Produto possui servicos ativos.'], 422);
        }

        $product->delete();

        return response()->json(['message' => 'Produto excluido com sucesso!']);
    }

    public function groups(Request $request): JsonResponse|View
    {
        if ($request->expectsJson()) {
            return response()->json(ProductGroup::withCount('products')->orderBy('sort_order')->get());
        }

        return view('admin.products.groups');
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $group = ProductGroup::create(array_merge(
            $request->only(['name', 'description', 'icon', 'color', 'show_on_order', 'sort_order', 'active']),
            ['slug' => Str::slug($request->name)]
        ));

        return response()->json(['message' => 'Grupo criado!', 'group' => $group], 201);
    }
}

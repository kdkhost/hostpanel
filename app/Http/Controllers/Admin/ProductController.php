<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $query = Product::with(['group:id,name', 'pricing'])
                ->withCount('services')
                ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->when($request->type,   fn($q) => $q->where('type', $request->type))
                ->when(!is_null($request->active), fn($q) => $q->where('active', $request->boolean('active')))
                ->orderBy($request->sort_by ?? 'sort_order', $request->sort_dir ?? 'asc');
            return response()->json($query->paginate($request->per_page ?? 20));
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
        $request->validate([
            'name'         => 'required|string|max:150',
            'type'         => 'required|in:shared,reseller,vps,dedicated,domain,addon,other',
            'billing_cycle_type' => 'required|in:one_time,recurring',
            'pricing'      => 'required|array|min:1',
            'pricing.*.billing_cycle' => 'required|string',
            'pricing.*.price'         => 'required|numeric|min:0',
        ]);

        $product = Product::create(array_merge(
            $request->only([
                'product_group_id', 'server_group_id', 'name', 'description', 'welcome_email',
                'type', 'module', 'billing_cycle_type', 'require_domain', 'auto_setup', 'auto_setup_type',
                'cpanel_pkg', 'disk_space', 'bandwidth', 'subdomains', 'email_accounts', 'databases',
                'ftp_accounts', 'ssl_free', 'featured', 'hidden', 'sort_order', 'active',
                'configurable_options', 'custom_fields', 'features',
            ]),
            ['slug' => \Illuminate\Support\Str::slug($request->name)]
        ));

        foreach ($request->pricing as $price) {
            ProductPricing::create([
                'product_id'    => $product->id,
                'currency'      => $price['currency'] ?? 'BRL',
                'billing_cycle' => $price['billing_cycle'],
                'price'         => $price['price'],
                'setup_fee'     => $price['setup_fee'] ?? 0,
                'active'        => true,
            ]);
        }

        return response()->json(['message' => 'Produto criado com sucesso!', 'product' => $product->load('pricing')], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:150']);

        $product->update($request->only([
            'product_group_id', 'server_group_id', 'name', 'description', 'welcome_email',
            'type', 'module', 'billing_cycle_type', 'require_domain', 'auto_setup', 'auto_setup_type',
            'cpanel_pkg', 'disk_space', 'bandwidth', 'subdomains', 'email_accounts', 'databases',
            'ftp_accounts', 'ssl_free', 'featured', 'hidden', 'sort_order', 'active',
            'configurable_options', 'custom_fields', 'features',
        ]));

        if ($request->has('pricing')) {
            foreach ($request->pricing as $price) {
                ProductPricing::updateOrCreate(
                    ['product_id' => $product->id, 'currency' => $price['currency'] ?? 'BRL', 'billing_cycle' => $price['billing_cycle']],
                    ['price' => $price['price'], 'setup_fee' => $price['setup_fee'] ?? 0, 'active' => $price['active'] ?? true]
                );
            }
        }

        return response()->json(['message' => 'Produto atualizado!', 'product' => $product->fresh()->load('pricing')]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->services()->where('status', 'active')->exists()) {
            return response()->json(['message' => 'Produto possui serviços ativos.'], 422);
        }
        $product->delete();
        return response()->json(['message' => 'Produto excluído com sucesso!']);
    }

    public function groups(): JsonResponse
    {
        return response()->json(ProductGroup::withCount('products')->orderBy('sort_order')->get());
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:100']);
        $group = ProductGroup::create(array_merge(
            $request->only(['name', 'description', 'icon', 'color', 'show_on_order', 'sort_order', 'active']),
            ['slug' => \Illuminate\Support\Str::slug($request->name)]
        ));
        return response()->json(['message' => 'Grupo criado!', 'group' => $group], 201);
    }
}

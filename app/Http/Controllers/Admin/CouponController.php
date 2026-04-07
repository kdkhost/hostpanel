<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->expectsJson()) return view('admin.coupons.index');
        $coupons = Coupon::when($request->search, fn($q) => $q->where('code', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')->paginate(20);
        return response()->json($coupons);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code'  => 'required|string|max:50|unique:coupons,code',
            'type'  => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
        ]);
        $coupon = Coupon::create($request->all());
        return response()->json(['message' => 'Cupom criado!', 'coupon' => $coupon], 201);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $request->validate(['code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id]);
        $coupon->update($request->all());
        return response()->json(['message' => 'Cupom atualizado!', 'coupon' => $coupon->fresh()]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();
        return response()->json(['message' => 'Cupom excluído!']);
    }
}

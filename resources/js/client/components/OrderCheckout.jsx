import React, { useState, useEffect } from 'react';
import axios from 'axios';

const BILLING_LABELS = {
    monthly: 'Mensal', quarterly: 'Trimestral', semiannually: 'Semestral',
    annually: 'Anual', biennially: 'Bienal', triennially: 'Trienal', free: 'Grátis',
};

const GATEWAY_LABELS = {
    pix: 'PIX', boleto: 'Boleto Bancário', credit_card: 'Cartão de Crédito',
    paypal: 'PayPal', mercadopago: 'Mercado Pago', credit_balance: 'Saldo de Crédito',
};

export default function OrderCheckout({ items: initialItems = [], gateways = [], creditBalance = 0 }) {
    const [items, setItems]       = useState(initialItems);
    const [coupon, setCoupon]     = useState('');
    const [couponData, setCouponData] = useState(null);
    const [couponError, setCouponError] = useState('');
    const [gateway, setGateway]   = useState(gateways[0]?.slug || '');
    const [loading, setLoading]   = useState(false);
    const [error, setError]       = useState('');

    const subtotal = items.reduce((acc, i) => acc + parseFloat(i.price || 0), 0);
    const discount = couponData
        ? (couponData.type === 'percentage' ? subtotal * (couponData.value / 100) : couponData.value)
        : 0;
    const total = Math.max(0, subtotal - discount);

    const applyGoupon = async () => {
        if (!coupon.trim()) return;
        setCouponError(''); setCouponData(null);
        try {
            const { data } = await axios.post('/cliente/pedidos/validar-cupom', { coupon_code: coupon, items });
            setCouponData(data.coupon);
        } catch (e) {
            setCouponError(e.response?.data?.message || 'Cupom inválido.');
        }
    };

    const placeOrder = async () => {
        setLoading(true); setError('');
        try {
            const { data } = await axios.post('/cliente/pedidos', {
                items: items.map(i => ({ product_id: i.product_id, billing_cycle: i.billing_cycle })),
                coupon_code: coupon || null,
                payment_method: gateway,
            });
            if (data.redirect) window.location.href = data.redirect;
            else if (data.order) window.location.href = `/cliente/pedidos/${data.order.id}`;
        } catch (e) {
            setError(e.response?.data?.message || 'Erro ao processar pedido.');
            setLoading(false);
        }
    };

    const removeItem = (idx) => setItems(prev => prev.filter((_, i) => i !== idx));

    return (
        <div className="max-w-2xl mx-auto space-y-6">
            {/* Items */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div className="px-5 py-4 border-b border-gray-100">
                    <h2 className="font-semibold text-gray-900">Itens do Pedido</h2>
                </div>
                <div className="divide-y divide-gray-50">
                    {items.map((item, idx) => (
                        <div key={idx} className="flex items-center justify-between px-5 py-4">
                            <div>
                                <div className="font-medium text-gray-900">{item.name}</div>
                                <div className="text-sm text-gray-500">{BILLING_LABELS[item.billing_cycle] || item.billing_cycle}</div>
                            </div>
                            <div className="flex items-center gap-4">
                                <span className="font-semibold text-gray-900">
                                    R$ {parseFloat(item.price || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                                </span>
                                <button onClick={() => removeItem(idx)} className="text-red-400 hover:text-red-600 text-sm">✕</button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Coupon */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <label className="block text-sm font-semibold text-gray-700 mb-2">Cupom de Desconto</label>
                <div className="flex gap-2">
                    <input
                        type="text"
                        className="flex-1 border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 uppercase"
                        placeholder="DESCONTO20"
                        value={coupon}
                        onChange={e => setCoupon(e.target.value.toUpperCase())}
                    />
                    <button onClick={applyGoupon} className="border border-gray-200 text-gray-700 hover:bg-gray-50 font-semibold px-4 py-2.5 rounded-lg text-sm">
                        Aplicar
                    </button>
                </div>
                {couponError && <p className="text-red-600 text-xs mt-1.5">{couponError}</p>}
                {couponData && (
                    <div className="mt-2 flex items-center gap-2 text-green-700 text-sm">
                        <span>✅ Cupom aplicado! Desconto de{' '}
                            {couponData.type === 'percentage' ? `${couponData.value}%` : `R$ ${couponData.value}`}
                        </span>
                    </div>
                )}
            </div>

            {/* Payment */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <label className="block text-sm font-semibold text-gray-700 mb-3">Forma de Pagamento</label>
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    {gateways.map(gw => (
                        <button
                            key={gw.slug}
                            onClick={() => setGateway(gw.slug)}
                            className={`border-2 rounded-xl p-3 text-center text-sm font-semibold transition ${
                                gateway === gw.slug ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-700 hover:border-gray-300'
                            }`}
                        >
                            <div className="text-xl mb-1">
                                {gw.slug === 'pix' ? '⚡' : gw.slug === 'boleto' ? '📄' : gw.slug === 'credit_card' ? '💳' : gw.slug === 'paypal' ? '🔵' : '💰'}
                            </div>
                            {GATEWAY_LABELS[gw.slug] || gw.name}
                        </button>
                    ))}
                </div>
            </div>

            {/* Summary */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div className="space-y-2 mb-4">
                    <div className="flex justify-between text-sm text-gray-600">
                        <span>Subtotal</span>
                        <span>R$ {subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                    </div>
                    {discount > 0 && (
                        <div className="flex justify-between text-sm text-green-600">
                            <span>Desconto</span>
                            <span>- R$ {discount.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                        </div>
                    )}
                    <div className="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-900">
                        <span>Total</span>
                        <span>R$ {total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                    </div>
                </div>

                {error && <p className="text-red-600 text-sm mb-3">{error}</p>}

                <button
                    onClick={placeOrder}
                    disabled={loading || items.length === 0}
                    className="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:opacity-90 text-white font-bold py-3.5 rounded-xl text-base disabled:opacity-50 transition"
                >
                    {loading ? (
                        <span className="inline-flex items-center gap-2 justify-center">
                            <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Processando...
                        </span>
                    ) : `Finalizar Pedido — R$ ${total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}
                </button>
            </div>
        </div>
    );
}

@extends('home.layouts.app')

@section('title', 'Contato — ' . config('app.name'))
@section('meta-description', 'Entre em contato conosco. Estamos prontos para ajudar com suas dúvidas e suporte.')

@php
$recaptchaEnabled = \App\Models\Setting::get('recaptcha.enabled', false);
$recaptchaSiteKey = \App\Models\Setting::get('recaptcha.site_key', '');
@endphp

@if($recaptchaEnabled && $recaptchaSiteKey)
@push('head')
<script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
@endpush
@endif

@section('content')
{{-- Hero Section --}}
<section class="bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold mb-4">Entre em Contato</h1>
        <p class="text-xl text-blue-200 max-w-2xl mx-auto">
            Estamos aqui para ajudar. Envie sua mensagem e responderemos em breve.
        </p>
    </div>
</section>

{{-- Contact Section --}}
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Contact Info Cards --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- WhatsApp --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 card-hover">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="bi bi-whatsapp text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">WhatsApp</h3>
                    <p class="text-gray-600 text-sm mb-3">Atendimento rápido via mensagem</p>
                    <a href="https://wa.me/5511999999999" target="_blank" class="text-green-600 font-semibold hover:underline">
                        (11) 99999-9999
                    </a>
                </div>

                {{-- Email --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 card-hover">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="bi bi-envelope text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">E-mail</h3>
                    <p class="text-gray-600 text-sm mb-3">Suporte técnico e vendas</p>
                    <a href="mailto:suporte@{{ request()->getHost() }}" class="text-blue-600 font-semibold hover:underline text-sm">
                        suporte@{{ request()->getHost() }}
                    </a>
                </div>

                {{-- Hours --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 card-hover">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="bi bi-clock text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">Horário de Atendimento</h3>
                    <p class="text-gray-600 text-sm">
                        Segunda a Sexta: 9h às 18h<br>
                        Sábado: 10h às 14h<br>
                        <span class="text-green-600 font-medium">Suporte 24/7 para clientes</span>
                    </p>
                </div>
            </div>

            {{-- Contact Form --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Envie uma mensagem</h2>

                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700">
                            <i class="bi bi-check-circle-fill mr-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    @if($errors->has('recaptcha'))
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700">
                            <i class="bi bi-exclamation-triangle-fill mr-2"></i>{{ $errors->first('recaptcha') }}
                        </div>
                    @endif

                    <form action="{{ route('contact.submit') }}" method="POST" class="space-y-6" id="contactForm">
                        @csrf
                        <input type="hidden" name="recaptcha_token" id="recaptchaToken">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Nome completo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                                    placeholder="Seu nome">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    E-mail <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                                    placeholder="seu@email.com">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Phone --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Telefone / WhatsApp
                                </label>
                                <input type="tel" name="phone"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                                    placeholder="(00) 00000-0000">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Subject --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Assunto <span class="text-red-500">*</span>
                                </label>
                                <select name="subject" required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition bg-white">
                                    <option value="">Selecione...</option>
                                    <option value="vendas">Vendas — Quero contratar</option>
                                    <option value="suporte">Suporte Técnico</option>
                                    <option value="financeiro">Financeiro</option>
                                    <option value="parceria">Parceria e Revenda</option>
                                    <option value="outro">Outro</option>
                                </select>
                                @error('subject')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Message --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Mensagem <span class="text-red-500">*</span>
                            </label>
                            <textarea name="message" rows="6" required
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition resize-none"
                                placeholder="Descreva sua dúvida..."></textarea>
                            @error('message')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-between pt-4">
                            <p class="text-sm text-gray-500">
                                @if($recaptchaEnabled)
                                    <i class="bi bi-shield-check mr-1"></i>Protegido por reCAPTCHA
                                @else
                                    <i class="bi bi-shield-check mr-1"></i>Seus dados estão protegidos
                                @endif
                            </p>
                            <button type="submit" id="submitBtn"
                                class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-3 px-8 rounded-xl transition transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="bi bi-send mr-2"></i>Enviar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Quick Links --}}
<section class="py-16 bg-white border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Recursos úteis</h2>
            <p class="text-gray-600">Talvez encontre sua resposta aqui</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('kb') }}" class="group p-6 rounded-2xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition text-center">
                <i class="bi bi-book text-3xl text-blue-600 mb-4 block"></i>
                <h3 class="font-bold text-gray-900 mb-2">Base de Conhecimento</h3>
                <p class="text-sm text-gray-600">Tutoriais e documentação</p>
            </a>

            <a href="{{ route('status.index') }}" class="group p-6 rounded-2xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition text-center">
                <i class="bi bi-activity text-3xl text-green-600 mb-4 block"></i>
                <h3 class="font-bold text-gray-900 mb-2">Status da Rede</h3>
                <p class="text-sm text-gray-600">Saúde dos servidores</p>
            </a>

            <a href="{{ route('client.login') }}" class="group p-6 rounded-2xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition text-center">
                <i class="bi bi-person-circle text-3xl text-purple-600 mb-4 block"></i>
                <h3 class="font-bold text-gray-900 mb-2">Área do Cliente</h3>
                <p class="text-sm text-gray-600">Acesse sua conta</p>
            </a>
        </div>
    </div>
</section>
@endsection

@if($recaptchaEnabled && $recaptchaSiteKey)
@push('scripts')
<script>
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split mr-2"></i>Verificando...';

        grecaptcha.ready(function() {
            grecaptcha.execute('{{ $recaptchaSiteKey }}', {action: 'contact_submit'})
                .then(function(token) {
                    document.getElementById('recaptchaToken').value = token;
                    document.getElementById('contactForm').submit();
                });
        });
    });
</script>
@endpush
@endif

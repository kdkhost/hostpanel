@extends('home.layouts.app')

@section('title', ($page->title ?? 'Página') . ' — ' . config('app.name'))
@section('meta-description', $page->meta_description ?? strip_tags(substr($page->content ?? '', 0, 160)))

@push('head')
<style>
    .prose h2 { font-size:1.25rem;font-weight:700;margin:1.5rem 0 .75rem;color:#1e293b }
    .prose h3 { font-size:1.1rem;font-weight:700;margin:1.25rem 0 .5rem;color:#334155 }
    .prose p  { margin-bottom:1rem;color:#475569;line-height:1.8 }
    .prose ul,.prose ol { padding-left:1.5rem;margin-bottom:1rem;color:#475569 }
    .prose li { margin-bottom:.4rem }
    .prose pre { background:#f1f5f9;border-radius:.5rem;padding:1rem;overflow-x:auto;margin:1rem 0 }
    .prose code { background:#f1f5f9;padding:.15rem .35rem;border-radius:.25rem;font-size:.875rem }
    .prose a { color:#1a56db;text-decoration:underline }
    .prose blockquote { border-left:4px solid #1a56db;padding:.5rem 1rem;background:#eff6ff;border-radius:0 .5rem .5rem 0;margin:1rem 0 }
    .prose img { max-width:100%;border-radius:.75rem;margin:1rem 0 }
    .prose table { width:100%;border-collapse:collapse;margin:1rem 0 }
    .prose th,.prose td { border:1px solid #e2e8f0;padding:.5rem .75rem;text-align:left }
    .prose th { background:#f8fafc;font-weight:600 }
</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
    <article class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 md:p-10">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-2">{{ $page->title }}</h1>
        @if($page->updated_at)
        <p class="text-gray-400 text-sm mb-6">Última atualização: {{ \Carbon\Carbon::parse($page->updated_at)->format('d/m/Y') }}</p>
        @endif
        <hr class="border-gray-100 mb-6">
        <div class="prose max-w-none">
            {!! $page->content !!}
        </div>
    </article>
</div>
@endsection

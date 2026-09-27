{{-- Kotak error validasi standar untuk halaman voter standalone. --}}
@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl mb-6 flex items-start gap-3" role="alert">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
        <div>@foreach ($errors->all() as $error)<p class="text-sm font-medium">{{ $error }}</p>@endforeach</div>
    </div>
@endif

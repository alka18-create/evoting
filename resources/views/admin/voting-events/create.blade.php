<x-layouts.admin title="Buat Event">
    <div class="max-w-2xl bg-white rounded-2xl shadow-sm border p-8">
        <form method="POST" action="{{ route('admin.voting-events.store') }}" class="space-y-5">
            @csrf
            <div><label class="block text-sm font-medium mb-1.5">Nama Event</label><input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm" placeholder="Pemilihan Serentak 2026"></div>
            <div><label class="block text-sm font-medium mb-1.5">Slug <span class="text-gray-400">(opsional)</span></label><input type="text" name="slug" value="{{ old('slug') }}" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm" placeholder="otomatis"></div>
            <div><label class="block text-sm font-medium mb-1.5">Deskripsi</label><textarea name="description" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm">{{ old('description') }}</textarea></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1.5">Mulai</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm"></div>
                <div><label class="block text-sm font-medium mb-1.5">Selesai</label><input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm"></div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t"><a href="{{ route('admin.voting-events.index') }}" class="px-5 py-2.5 text-sm hover:bg-gray-100 rounded-xl">Batal</a><button class="bg-primary-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold">Simpan</button></div>
        </form>
    </div>
</x-layouts.admin>

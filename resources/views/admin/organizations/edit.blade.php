<x-layouts.admin title="Edit Organisasi">
    <div class="max-w-xl bg-white rounded-2xl shadow-sm border p-8">
        <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')
            <div><label class="block text-sm font-medium mb-1.5">Nama Organisasi</label><input type="text" name="name" value="{{ old('name', $organization->name) }}" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm"></div>
            <div><label class="block text-sm font-medium mb-1.5">Slug</label><input type="text" name="slug" value="{{ old('slug', $organization->slug) }}" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm"></div>
            <div><label class="block text-sm font-medium mb-1.5">Deskripsi</label><textarea name="description" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm">{{ old('description', $organization->description) }}</textarea></div>
            <div><label class="block text-sm font-medium mb-1.5">Logo</label>@if($organization->logo_path)<img src="{{ asset('storage/'.$organization->logo_path) }}" class="w-20 h-20 rounded-xl object-cover mb-2">@endif<input type="file" name="logo" accept="image/*" class="w-full text-sm"></div>
            <div class="flex justify-end gap-3 pt-4 border-t"><a href="{{ route('admin.organizations.index') }}" class="px-5 py-2.5 text-sm hover:bg-gray-100 rounded-xl">Batal</a><button class="bg-primary-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold">Simpan</button></div>
        </form>
    </div>
</x-layouts.admin>

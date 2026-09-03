<x-layouts.admin title="Pengguna">
    <div class="flex justify-between items-center mb-6">
        <div>
            <p class="text-sm text-gray-500">Kelola semua akun pengguna</p>
        </div>
        <a href="{{ route('admin.users.create') }}"
            class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold py-2.5 px-5 rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Tambah Pengguna
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0
                                        {{ $user->role->value === 'SUPER_ADMIN' ? 'bg-purple-100' : ($user->role->value === 'ADMIN' ? 'bg-blue-100' : 'bg-amber-100') }}">
                                        <span class="text-sm font-bold
                                            {{ $user->role->value === 'SUPER_ADMIN' ? 'text-purple-600' : ($user->role->value === 'ADMIN' ? 'text-blue-600' : 'text-amber-600') }}">
                                            {{ substr($user->name, 0, 1) }}
                                        </span>
                                    </div>
                                    <span class="font-semibold text-gray-800">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ $user->username }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $roleColors = [
                                        'SUPER_ADMIN' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                        'ADMIN' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                        'OPERATOR' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $roleColors[$user->role->value] ?? 'bg-gray-50 text-gray-600' }}">
                                    {{ $user->role->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-red-50 text-red-700 ring-red-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Pasif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($user->id !== auth()->id())
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors ring-1 ring-inset text-blue-700 bg-blue-50 hover:bg-blue-100 ring-blue-600/20">
                                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors ring-1 ring-inset
                                                {{ $user->is_active
                                                    ? 'text-red-700 bg-red-50 hover:bg-red-100 ring-red-600/20'
                                                    : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 ring-emerald-600/20' }}">
                                                @if ($user->is_active)
                                                    <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                                                    Nonaktifkan
                                                @else
                                                    <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                                                    Aktifkan
                                                @endif
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
                                            x-data="{ show: false }" @click.away="show = false">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" @click="show = true"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors ring-1 ring-inset text-red-700 bg-red-50 hover:bg-red-100 ring-red-600/20">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                Hapus
                                            </button>
                                            <div x-show="show" x-transition class="absolute mt-2 p-4 bg-white rounded-xl shadow-lg border border-gray-200 z-10" style="display: none;">
                                                <p class="text-sm text-gray-700 mb-3">Hapus pengguna "{{ $user->name }}"?</p>
                                                <div class="flex gap-2">
                                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700">
                                                        Ya, Hapus
                                                    </button>
                                                    <button type="button" @click="show = false" class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                                        Batal
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Akun Anda</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <i data-lucide="users" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada pengguna.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <script>lucide.createIcons();</script>
</x-layouts.admin>

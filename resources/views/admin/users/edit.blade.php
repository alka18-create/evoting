<x-layouts.admin title="Edit Pengguna">
    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">Edit Pengguna</h3>
                <p class="text-sm text-gray-500 mt-1">Perbarui informasi pengguna</p>
            </div>

            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-6">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                            <input type="text" name="username" value="{{ old('username', $user->username) }}" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                        <select name="role" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" {{ old('role', $user->role->value) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Password Baru (opsional)</label>
                            <input type="password" name="password"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                placeholder="Kosongkan jika tidak diubah">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                placeholder="Ulangi password">
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span>Status:
                            @if ($user->is_active)
                                <span class="text-emerald-600 font-medium">Aktif</span>
                            @else
                                <span class="text-red-600 font-medium">Pasif</span>
                            @endif
                        </span>
                        <span>Login Terakhir: {{ $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : 'Belum pernah' }}</span>
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit"
                        class="bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold py-2.5 px-6 rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.users.index') }}"
                        class="px-6 py-2.5 border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>

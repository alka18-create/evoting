<x-layouts.admin title="Tambah Pengguna">
    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">Tambah Pengguna Baru</h3>
                <p class="text-sm text-gray-500 mt-1">Buat akun pengguna baru</p>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="p-6">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name') }}" required autofocus
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                            placeholder="Masukkan nama lengkap">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                            <input type="text" name="username" value="{{ old('username') }}" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                placeholder="Masukkan username">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                placeholder="Masukkan email">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                        <select name="role" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                            <option value="">Pilih Role</option>
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                            <input type="password" name="password" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                placeholder="Minimal 8 karakter">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                placeholder="Ulangi password">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit"
                        class="bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold py-2.5 px-6 rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                        Buat Pengguna
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

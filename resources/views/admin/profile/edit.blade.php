<x-layouts.admin title="Profile">
    <div class="max-w-2xl">
        <!-- Profile Info -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">Informasi Profile</h3>
                <p class="text-sm text-gray-500 mt-1">Perbarui informasi akun Anda</p>
            </div>

            <form method="POST" action="{{ route('admin.profile.update') }}" class="p-6">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                            <input type="text" value="{{ $user->username }}" disabled
                                class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm text-gray-500 cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                            <input type="text" value="{{ $user->role->label() }}" disabled
                                class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm text-gray-500 cursor-not-allowed">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Login Terakhir</label>
                        <input type="text" value="{{ $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : 'Belum pernah login' }}" disabled
                            class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm text-gray-500 cursor-not-allowed">
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit"
                        class="bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold py-2.5 px-6 rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">Ubah Password</h3>
                <p class="text-sm text-gray-500 mt-1">Pastikan password baru minimal 8 karakter</p>
            </div>

            <form method="POST" action="{{ route('admin.profile.password') }}" class="p-6">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password Lama</label>
                        <input type="password" name="current_password" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password Baru</label>
                        <input type="password" name="password" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit"
                        class="bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold py-2.5 px-6 rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                        Ubah Password
                    </button>
                </div>
            </form>
        </div>

        <!-- P3-01: Verifikasi Dua Langkah -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Verifikasi Dua Langkah</h3>
                    <p class="text-sm text-gray-500 mt-1">TOTP authenticator + kode pemulihan</p>
                </div>
                <a href="{{ route('admin.profile.mfa') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Kelola &rarr;</a>
            </div>
        </div>
    </div>
</x-layouts.admin>

<x-layouts.admin title="Edit Pemilih">
    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Edit: {{ $voter->name }}</h3>
                <p class="text-sm text-gray-500 mt-1">Perbarui data pemilih</p>
            </div>

            <form method="POST" action="{{ route('admin.voters.update', $voter) }}" class="p-6">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">NIS / Student ID</label>
                        <input type="text" name="student_id" value="{{ old('student_id', $voter->student_id) }}" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name', $voter->name) }}" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Kelas</label>
                        <input type="text" name="class_name" value="{{ old('class_name', $voter->class_name) }}" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit"
                        class="bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold py-2.5 px-6 rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.voters.index') }}"
                        class="px-6 py-2.5 border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>

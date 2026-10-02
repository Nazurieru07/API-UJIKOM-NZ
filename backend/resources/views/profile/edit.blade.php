@extends($user->role === 'peminjam' ? 'layouts.peminjam' : 'layouts.app')

@section('title', 'Profil Saya')
@section('header-title', 'Profil Saya')

@section('content')

    @if(session('success'))
        <div class="flash-success fixed top-20 right-4 z-50 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl shadow-lg text-sm max-w-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="flash-error fixed top-20 right-4 z-50 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-lg text-sm max-w-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="max-w-3xl mx-auto">

        {{-- Breadcrumb: kembali ke halaman terakhir yang benar-benar
             berbeda dari halaman profil ini.

             Laravel bawaan cuma menyimpan satu "url sebelumnya" yang
             TERTIMPA setiap request GET -- termasuk request ke /profile
             sendiri. Akibatnya kalau halaman profil direfresh, tombol
             akan menunjuk kembali ke /profile (loop ke diri sendiri).
             SimpanRiwayatHalaman menyimpan daftar halaman sehingga
             helper urlSebelumnya() bisa ambil halaman terakhir yang
             berbeda. --}}
        @php
            $urlKembali = \App\Http\Middleware\SimpanRiwayatHalaman::urlSebelumnya(url()->current());
            $targetKembali = $urlKembali ?: route('profile.edit');
        @endphp

        <div class="mb-4 flex items-center justify-between gap-3">

            @if($urlKembali)
                <a href="{{ $targetKembali }}"
                   class="inline-flex items-center gap-2 text-sm font-medium text-gray-600
                          hover:text-gray-900 transition">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-4 h-4"
                         fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>

                    Kembali
                </a>
            @else
                <span class="inline-flex items-center gap-2 text-sm text-gray-400">
                    {{-- Tidak ada riwayat: halaman ini dibuka langsung
                         (misal dari bookmark), tidak ada tujuan kembali. --}}
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-4 h-4"
                         fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>

                    Profil
                </span>
            @endif

            <span class="text-sm text-gray-400">
                Profil Saya
            </span>

        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-8 flex items-center gap-6">
                @if($user->foto_profile)
                    <img id="fotoHeader" src="{{ asset($user->foto_profile) }}" alt="{{ $user->name }}" class="w-24 h-24 rounded-full object-cover border-4 border-white/30 shadow-lg">
                @else
                    <div id="fotoHeader" class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center text-white text-3xl font-bold border-4 border-white/30">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif

                <div>
                    <h2 class="text-2xl font-bold text-white">{{ $user->name }}</h2>
                    <p class="text-blue-100 text-sm mt-1">{{ $user->email }}</p>
                    <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold bg-white/90 text-blue-800">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Foto Profil</label>

                    <div class="flex items-center gap-4">
                        @if($user->foto_profile)
                            <img id="fotoPreview" src="{{ asset($user->foto_profile) }}" alt="Preview" class="w-20 h-20 rounded-full object-cover border">
                        @else
                            <div id="fotoPreview" class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-2xl border">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif

                        <div class="flex-1">
                            <input type="file" name="foto_profile" id="fotoInput" accept="image/*" class="hidden">

                            <button type="button" onclick="document.getElementById('fotoInput').click()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                                Pilih Foto
                            </button>

                            <p class="text-xs text-gray-400 mt-2">JPG, JPEG, PNG, atau WebP. Maksimal 2MB.</p>
                        </div>
                    </div>

                    @error('foto_profile')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('name')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('email')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="no_hp" class="block text-sm font-semibold text-gray-700 mb-2">No. HP</label>
                        <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="08xxxxxxxxxx"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('no_hp')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="jenis_kelamin" class="block text-sm font-semibold text-gray-700 mb-2">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih...</option>
                            <option value="Laki-laki" {{ old('jenis_kelamin', $user->jenis_kelamin) === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="Perempuan" {{ old('jenis_kelamin', $user->jenis_kelamin) === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                        @error('jenis_kelamin')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-1">Ganti Password</h3>
                    <p class="text-xs text-gray-500 mb-4">Opsional. Biarkan kosong jika tidak ingin mengubah password.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password Baru</label>
                            <input type="password" id="password" name="password" autocomplete="new-password"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('password')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Ulangi Password Baru</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>

    </div>

@endsection

@push('scripts')
<script>
    const fotoInput = document.getElementById('fotoInput');

    if (fotoInput) {
        fotoInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (ev) {
                document.getElementById('fotoPreview').src = ev.target.result;
                document.getElementById('fotoHeader').src = ev.target.result;
            };
            reader.readAsDataURL(file);
        });
    }
</script>
@endpush

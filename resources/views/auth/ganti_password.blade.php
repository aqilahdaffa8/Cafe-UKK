@php
    $wajib = auth()->user()->wajib_ganti_password;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti Password | Kedai Kopi Senja</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-50 px-4 font-sans text-gray-900">

    <main class="w-full max-w-sm">
        <div class="mb-6 text-center">
            <p class="text-lg font-bold">Kedai Kopi Senja</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}"
              class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <h1 class="text-xl font-bold">Ganti password</h1>
                <p class="text-sm text-gray-500">Gunakan password yang sulit ditebak.</p>
            </div>

            @if ($wajib)
                <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
                    <b>Anda wajib mengganti password</b> sebelum melanjutkan.
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <label class="flex flex-col gap-1 text-sm">
                <span>Password lama</span>
                <input type="password" name="password_lama" required autofocus autocomplete="current-password"
                       class="h-10 rounded-md border px-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black
                              {{ $errors->has('password_lama') ? 'border-red-400' : 'border-gray-300' }}">
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span>Password baru</span>
                <input type="password" name="password" required autocomplete="new-password"
                       class="h-10 rounded-md border px-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black
                              {{ $errors->has('password') ? 'border-red-400' : 'border-gray-300' }}">
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span>Konfirmasi password baru</span>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="h-10 rounded-md border border-gray-300 px-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
            </label>

            <label class="flex items-center gap-2 text-xs text-gray-600">
                <input type="checkbox" id="lihatSemua" class="rounded border-gray-300">
                Tampilkan password
            </label>

            <div class="flex gap-2">
                @unless ($wajib)
                    <a href="{{ url('/') }}"
                       class="flex h-10 flex-1 items-center justify-center rounded-md border border-gray-300 text-sm hover:bg-gray-50">
                        Batal
                    </a>
                @endunless
                <button type="submit"
                        class="h-10 flex-1 rounded-md bg-black text-sm font-medium text-white hover:bg-gray-800">
                    Simpan
                </button>
            </div>
        </form>
    </main>

    <script>
        // Tampilkan atau sembunyikan ketiga kolom password sekaligus
        document.getElementById('lihatSemua').addEventListener('change', function () {
            document.querySelectorAll('input[name^="password"]').forEach(function (el) {
                el.type = this.checked ? 'text' : 'password';
            }, this);
        });
    </script>
</body>
</html>
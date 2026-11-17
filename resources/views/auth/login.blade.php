<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Staf | Kedai Kopi Senja</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-50 px-4 font-sans text-gray-900">

    <main class="w-full max-w-sm">
        <div class="mb-6 text-center">
            <p class="text-lg font-bold">Kedai Kopi Senja</p>
            <p class="mt-1 text-sm text-gray-500">Masuk untuk mengelola kafe.</p>
        </div>

        <form method="POST" action="{{ route('login') }}"
              class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <h1 class="text-xl font-bold">Login staf</h1>
                <p class="text-sm text-gray-500">Gunakan akun manajer atau kasir.</p>
            </div>

            @error('email')
                <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $message }}</div>
            @enderror

            <label class="flex flex-col gap-1 text-sm">
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       autocomplete="username" placeholder="nama@kedaikopisenja.id"
                       class="h-10 rounded-md border px-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black
                              {{ $errors->has('email') ? 'border-red-400' : 'border-gray-300' }}">
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span>Password</span>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                           autocomplete="current-password"
                           class="h-10 w-full rounded-md border border-gray-300 px-3 pr-16 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                    <button type="button" id="togglePassword"
                            class="absolute inset-y-0 right-0 px-3 text-xs text-gray-500 hover:text-gray-900">
                        Lihat
                    </button>
                </div>
            </label>

            <button type="submit"
                    class="h-10 w-full rounded-md bg-black text-sm font-medium text-white hover:bg-gray-800">
                Masuk
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-gray-500">&copy; {{ date('Y') }} Kedai Kopi Senja</p>
    </main>

    <script>
        // Tampilkan atau sembunyikan password
        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';
            this.textContent = tampil ? 'Sembunyikan' : 'Lihat';
        });
    </script>
</body>
</html>
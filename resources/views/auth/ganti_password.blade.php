<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ganti Password</title>
</head>
<body>
    <h1>Ganti Password</h1>

    @if (auth()->user()->wajib_ganti_password)
        <p><strong>Anda wajib mengganti password sebelum melanjutkan.</strong></p>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('PUT')

        <div>
            <label>Password lama
                <input type="password" name="password_lama" required>
            </label>
        </div>

        <div>
            <label>Password baru
                <input type="password" name="password" required>
            </label>
        </div>

        <div>
            <label>Konfirmasi password baru
                <input type="password" name="password_confirmation" required>
            </label>
        </div>

        @foreach ($errors->all() as $error)
            <p style="color: red;">{{ $error }}</p>
        @endforeach

        <button type="submit">Simpan</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Keluar</button>
    </form>
</body>
</html>
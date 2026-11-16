<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login Staf</title>
</head>
<body>
    <h1>Login Staf</h1>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>
        </div>

        <div>
            <label>Password
                <input type="password" name="password" required>
            </label>
        </div>

        @error('email')
            <p style="color: red;">{{ $message }}</p>
        @enderror

        <button type="submit">Masuk</button>
    </form>
</body>
</html>
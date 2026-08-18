<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TaskFlow</title>
</head>

<body>

    <h1>TaskFlow Login</h1>

    @if ($errors->any())
        <div>
            @foreach ($errors->all() as $error)
                <p style="color: red;">
                    {{ $error }}
                </p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div>
            <label for="email">Email</label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
            >
        </div>

        <br>

        <div>
            <label for="password">Password</label>

            <input
                id="password"
                type="password"
                name="password"
                required
            >
        </div>

        <br>

        <button type="submit">
            Login
        </button>
    </form>

    <p>
        Don't have an account?
        <a href="{{ route('register') }}">
            Register
        </a>
    </p>

</body>
</html>
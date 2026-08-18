<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TaskFlow</title>
</head>

<body>

    <h1>TaskFlow Dashboard</h1>

    <h2>
        Welcome, {{ auth()->user()->name }}!
    </h2>

    <p>
        Email: {{ auth()->user()->email }}
    </p>

    <p>
        Role:
        {{ auth()->user()->getRoleNames()->implode(', ') }}
    </p>

    <p>
        Can create project:
        {{ auth()->user()->can('projects.create') ? 'YES' : 'NO' }}
    </p>

    <p>
        Can view projects:
        {{ auth()->user()->can('projects.view') ? 'YES' : 'NO' }}
    </p>

    <p>
        User ID:
        {{ auth()->user()->id }}
    </p>

    <p>
        You are successfully authenticated.
    </p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit">
            Logout
        </button>
    </form>

</body>
</html>
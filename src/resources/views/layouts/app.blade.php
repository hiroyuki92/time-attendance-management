<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Coachtech</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
        @yield('css')
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
</head>
<body>
    <header class="header">
        <div class="header__logo">
            <img src="{{ asset('images/logo.svg') }}" alt="サイトのロゴ">
            @yield('link')
        </div>
    </header>
    <div class="content">
        @yield('content')
    </div>
</body>
</html>
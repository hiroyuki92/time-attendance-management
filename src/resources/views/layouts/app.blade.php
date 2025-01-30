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
        </div>
        <nav class="header__nav">
            @auth
                <ul class="header__menu">
                    @if (isset($isCheckedOut) && $isCheckedOut)
                    {{-- 退勤済みのメニュー --}}
                        <li><a href="{{ route('attendance.index') }}">今月の出勤一覧</a></li>
                        <li><a href="{{ route('requests.index') }}">申請</a></li>
                    @else
                    {{-- 勤務中のメニュー --}}
                        <li><a href="{{ route('attendance.create') }}">勤怠</a></li>
                        <li><a href="{{ route('attendance.index') }}">勤怠一覧</a></li>
                        <li><a href="{{ route('requests.index') }}">申請</a></li>
                    @endif
                    <li>
                        <form action="{{ request()->is('admin/*') ? route('admin.logout') : route('logout') }}" method="post">
                            @csrf
                            <button class="header__logout">ログアウト</button>
                        </form>
                    </li>
                </ul>
            @endauth
        </nav>
    </header>
    <div class="content">
        @yield('content')
    </div>
</body>
</html>
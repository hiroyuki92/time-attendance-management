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
        <button class="hamburger-menu">
            <span class="hamburger-line"></span>
        </button>
        <nav class="header__nav">
            @auth
                <ul class="header__menu">
                    {{-- 管理者でない場合のメニュー --}}
                    @if (!request()->is('admin/*'))
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
                    @endif

                    {{-- 管理者用のスタッフ一覧ボタン --}}
                    @if (request()->is('admin/*'))
                        <li><a href="{{ route('admin.attendance.list') }}">勤怠一覧</a></li>
                        <li><a href="{{ route('admin.staff.index') }}">スタッフ一覧</a></li>
                        <li><a href="{{ route('admin.requests.index') }}">申請一覧</a></li>
                    @endif
                    <li>
                        <form class="header__logout-button" action="{{ request()->is('admin/*') ? route('admin.logout') : route('logout') }}" method="post">
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger-menu');
    const nav = document.querySelector('.header__nav');
    const menu = document.querySelector('.header__menu');

    if (hamburger && nav) {
        hamburger.addEventListener('click', function(e) {
            e.stopPropagation(); // イベントの伝播を止める
            this.classList.toggle('active');
            nav.classList.toggle('active');
        });

        // メニューの外側をクリックした時に閉じる
        document.addEventListener('click', function(e) {
            if (!nav.contains(e.target) && !hamburger.contains(e.target)) {
                hamburger.classList.remove('active');
                nav.classList.remove('active');
            }
        });

        // メニュー内のリンクをクリックした時にメニューを閉じる
        nav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                nav.classList.remove('active');
            });
        });

        // 画面サイズが変更された時の処理
        window.addEventListener('resize', () => {
            if (window.innerWidth < 768 || window.innerWidth > 850) {
                hamburger.classList.remove('active');
                nav.classList.remove('active');
            }
        });
    }
});
</script>
</body>
</html>

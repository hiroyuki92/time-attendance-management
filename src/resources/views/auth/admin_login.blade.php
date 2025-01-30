@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/authentication.css')}}">
@endsection

@section('content')
<form action="{{ route('admin.login') }}" method="post" class="authenticate center">
    @csrf
    <h1 class="page__title">ログイン</h1>
    <label for="mail" class="entry__name">メールアドレス</label>
    <input name="email" id="mail" type="email" class="input" value="{{ old('email') }}">
    <div class="form__error">
        @error('email')
        {{ $message }}
        @enderror
    </div>
    <label for="password" class="entry__name">パスワード</label>
    <input name="password" id="password" type="password" class="input">
    <div class="form__error">
        @error('password')
        {{ $message }}
        @enderror
    </div>
    <button class="btn btn--big">管理者ログインする</button>
</form>
<a href="/login" class="link">ユーザーログインはこちら</a>
@endsection
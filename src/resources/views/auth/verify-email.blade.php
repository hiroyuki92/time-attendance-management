@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('content')
<main class="container center">
    <div class=" authentication_content">
        <p>登録していただいたメールアドレスに認証メールを送付しました。</p>
        <p>メール認証を完了してください。</p>

        @if(session('message'))
            <p>{{ session('message') }}</p>
        @endif

        @if($errors->any())
            <p>{{ $errors->first() }}</p>
        @endif
    </div>
    <form method="POST" action="{{ route('verification.resend') }}" class="authentication_button">
        @csrf
        <button type="submit" class="link">認証メールを再送する</button>
    </form>
</main>
@endsection

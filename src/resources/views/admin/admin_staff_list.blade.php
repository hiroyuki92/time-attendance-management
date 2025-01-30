@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff.css')}}">
@endsection

@section('content')
<main class="container center">
    <div class="staff__list">
        <div class="vertical-bar"></div>
        <h1 class="heading-text">スタッフ一覧</h1>
    </div>
    <div class="table-container">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><a href="{{ route('admin.attendance.staff.show', ['id' => $user->id]) }}" class="detail-link">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</main>
@endsection
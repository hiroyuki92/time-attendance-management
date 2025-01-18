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
                <tr>
                    <td>西玲奈</td>
                    <td>reina@coachtech.com</td>
                    <td><a href="/attendance/1" class="detail-link">詳細</a></td>
                </tr>
                <tr>
                    <td>西玲奈</td>
                    <td>reina@coachtech.com</td>
                    <td><a href="/attendance/1" class="detail-link">詳細</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</main>
@endsection
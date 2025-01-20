@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('content')
<main class="container center">
    <div class="attendance__list">
        <div class="vertical-bar"></div>
        <h1 class="heading-text">申請一覧</h1>
    </div>
    <div class="tabs">
        <button class="tab">
            承認待ち
        </button>
        <button class="tab">
            承認済み
        </button>
    </div>
    <div class="table-container">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>承認待ち</td>
                    <td>西玲奈</td>
                    <td>2023/06/01</td>
                    <td>遅延のため</td>
                    <td>2023/06/02</td>
                    <td><a href="/attendance/1" class="detail-link">詳細</a></td>
                </tr>
                <tr>
                    <td>承認待ち</td>
                    <td>西玲奈</td>
                    <td>2023/06/01</td>
                    <td>遅延のため</td>
                    <td>2023/06/02</td>
                    <td><a href="/attendance/1" class="detail-link">詳細</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</main>
@endsection
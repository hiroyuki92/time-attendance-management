@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('content')
<main class="container center">
    <div class="attendance__list">
        <div class="vertical-bar"></div>
        <h1 class="heading-text">勤怠一覧</h1>
    </div>
    <nav class="month-nav">
        <button class="month-nav-button">
            <span class="arrow arrow-left"></span>
            前日
        </button>

        <div class="current-month">
            <svg class="calendar-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            2023/06/01
        </div>

        <button class="month-nav-button">
            翌日
            <span class="arrow arrow-right"></span>
        </button>
    </nav>
    <div class="table-container">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>山田 太郎</td>
                    <td>09:00</td>
                    <td>18:00</td>
                    <td>01:00</td>
                    <td>08:00</td>
                    <td><a href="/attendance/1" class="detail-link">詳細</a></td>
                </tr>
                <tr>
                    <td>西 玲奈</td>
                    <td>09:00</td>
                    <td>18:00</td>
                    <td>01:00</td>
                    <td>08:00</td>
                    <td><a href="/attendance/1" class="detail-link">詳細</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</main>
@endsection
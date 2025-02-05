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
            <span class="arrow">←</span>
            <a href="{{ route('attendance.index', ['month' => $previousMonth]) }}" class="btn btn-primary">前月</a>
        </button>

        <div class="current-month">
            <svg class="calendar-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            {{ $currentMonth }}
        </div>

        <button class="month-nav-button">
            <a href="{{ route('attendance.index', ['month' => $nextMonth]) }}" class="btn btn-primary">翌月</a>
            <span class="arrow">→</span>
        </button>
    </nav>
    <div class="table-container">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $attendance)
                <tr>
                    <td>{{ formatJapaneseDate($attendance->work_date) }}</td>
                    <td>{{ $attendance->clock_in ? $attendance->clock_in->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->break_time ?? '00:00' }}</td>
                    <td>{{ $attendance->total_work_time }}</td>
                    <td><a href="{{ route('attendance.show', $attendance->id) }}" class="detail-link">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</main>
@endsection
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('content')
<main class="container center">
    <div class="attendance">
        <div class="status-badge">
            <div class="status-badge__item {{ $latestAttendanceStatus }}">
            @if ($latestAttendanceStatus === 'no_record')
                勤務外
            @elseif ($latestAttendanceStatus === 'working')
                出勤中
            @elseif ($latestAttendanceStatus === 'breaking')
                休憩中
            @elseif ($latestAttendanceStatus === 'left')
                退勤済
            @endif
        </div>
        <h1 class="date">{{ $date }}</h1>
        <div class="time">{{ date('H:i') }}</div>
        <div class="attendance-buttons">
            @if ($latestAttendanceStatus === 'no_record')
            {{-- 勤怠データがない場合は出勤ボタンのみ --}}
            <form action="{{ route('attendance.start') }}" method="POST">
                @csrf
                <button type="submit" class="attendance-btn">出勤</button>
            </form>
        @elseif ($latestAttendanceStatus === 'working')
            {{-- 出勤中の場合は退勤と休憩ボタンを表示 --}}
            <form action="{{ route('attendance.end') }}" method="POST">
                @csrf
                <button type="submit" class="attendance-btn">退勤</button>
            </form>
            <form action="{{ route('attendance.break.start') }}" method="POST">
                @csrf
                <button type="submit" class="attendance-btn break-btn">休憩</button>
            </form>
        @elseif ($latestAttendanceStatus === 'breaking')
            {{-- 休憩中の場合は休憩戻りボタンを表示 --}}
            <form action="{{ route('attendance.break.end') }}" method="POST">
                @csrf
                <button type="submit" class="attendance-btn break-return-btn">休憩戻</button>
            </form>
        @elseif ($latestAttendanceStatus === 'left')
            {{-- 退勤済みの場合はメッセージを表示 --}}
            <div class="leaving_message">お疲れ様でした。</div>
        @endif
        </div>
    </div>
</main>
@endsection
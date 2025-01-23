@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('content')
<main class="container center">
    <div class="attendance">
        <div class="status-badge">
            @if (!$latestAttendance)
                <div class="status-badge__item">勤務外</div>
            @elseif ($latestAttendance->status === 'working')
                <div class="status-badge__item working">出勤中</div>
            @elseif ($latestAttendance->status === 'breaking')
                <div class="status-badge__item breaking">休憩中</div>
            @elseif ($latestAttendance->status === 'left')
                <div class="status-badge__item left">退勤済</div>
            @endif
        </div>
        <h1 class="date">{{ $date }}</h1>
        <div class="time">{{ date('H:i') }}</div>
        <div class="attendance-buttons">
            @if (!$latestAttendance)
                {{-- 未出勤の場合は出勤ボタンのみ表示 --}}
                <form action="{{ route('attendance.start') }}" method="POST">
                    @csrf
                    <button type="submit" class="attendance-btn">出勤</button>
                </form>
            @elseif ($latestAttendance && !$latestAttendance->clock_out)
                @if ($latestAttendance->status === 'breaking')
                {{-- 休憩中の場合は休憩戻りボタンを表示 --}}
                    <form action="{{ route('attendance.break.end') }}" method="POST">
                        @csrf
                        <button type="submit" class="attendance-btn break-return-btn">休憩戻</button>
                    </form>
                @else
                    <form action="{{ route('attendance.end') }}" method="POST" class="mr-2">
                        @csrf
                        <button type="submit" class="attendance-btn">退勤</button>
                    </form>
                    <form action="{{ route('attendance.break.start') }}" method="POST">
                        @csrf
                        <button type="submit" class="attendance-btn break-btn">休憩</button>
                    </form>
                @endif
                @elseif ($latestAttendance->status === 'left')
                    <div class="leaving_message">
                        お疲れ様でした。
                    </div>
            @endif
        </div>
    </div>
</main>
@endsection
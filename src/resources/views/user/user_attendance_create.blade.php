@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('content')
<main class="container center">
    <div class="attendance">
        <span class="status-badge">勤務外</span>
        <h1 class="date">{{ $date }}</h1>
        <div class="time">{{ date('H:i') }}</div>
        <div>
            <button class="attendance-btn">出勤</button>
        </div>
    </div>
</main>
@endsection
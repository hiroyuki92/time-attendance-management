@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('content')
<main class="container center">
    <div class="attendance__list">
        <div class="vertical-bar"></div>
        <h1 class="heading-text">勤怠詳細</h1>
    </div>
    <div class="form-container">
        <div class="form-group">
                <label>名前</label>
                <span class="time-range">西 伶奈</span>
        </div>
        <div class="form-group">
            <label>日付</label>
            <div class="time-range">
                <input type="text" value="2023年">
                <input type="text" value="6月1日">
            </div>
        </div>
        <div class="form-group">
            <label>出勤・退勤</label>
            <div class="time-range">
                <input type="text" value="09:00">
                <span>～</span>
                <input type="text" value="18:00">
            </div>
        </div>
        <div class="form-group">
            <label>休憩</label>
            <div class="time-range">
                <input type="text" value="12:00">
                <span>～</span>
                <input type="text" value="13:00">
            </div>
        </div>
        <div class="form-group">
            <label>休憩2</label>
            <div class="time-range">
                <input type="text" value="">
                <span>～</span>
                <input type="text" value="">
            </div>
        </div>
        <div class="form-group">
            <label>備考</label>
            <div class="form-text">
                <input class="form-text-content" type="text" value="">
            </div>
        </div>
    </div>
    <div class="button-container">
        <button class="submit-btn">修正</button>
    </div>
</main>
@endsection
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
                <div class="time-range">{{ $user->name }}</div>
        </div>
        <div class="form-group">
            <label>日付</label>
            <div class="form-group_content">
                <div class="form-group_content-detail">2023年</div>
                <div class="form-group_content-detail">6月1日</div>
            </div>
        </div>
        <div class="form-group">
            <label>出勤・退勤</label>
            <div class="form-group_content">
                <div class="form-group_content-detail">09:00</div>
                <span>～</span>
                <div class="form-group_content-detail">18:00</div>
            </div>
        </div>
        <div class="form-group">
            <label>休憩</label>
            <div class="form-group_content">
                <div class="form-group_content-detail">12:00</div>
                <span>～</span>
                <div class="form-group_content-detail">13:00</div>
            </div>
        </div>
        <div class="form-group">
            <label>休憩2</label>
            <div class="form-group_content">
                <div class="form-group_content-detail">12:00</div>
                <span>～</span>
                <div class="form-group_content-detail">13:00</div>
            </div>
        </div>
        <div class="form-group">
            <label>備考</label>
            <div class="form-text">
                <div class="form-group_content-detail">電車遅延のため</div>
            </div>
        </div>
    </div>
    @if($modRequest->status == 'approved')
        <div class="button-container">
            <button type="button" class="submit-btn-disabled" disabled>承認済み</button>
        </div>
    @else
        <form class="button-container" action="{{ route('admin.requests.approve', ['attendance_correct_request' => $modRequest->id]) }}" method="POST">
    @csrf
        <button type="submit" class="submit-btn">承認</button>
        </form>
    @endif
</main>
@endsection
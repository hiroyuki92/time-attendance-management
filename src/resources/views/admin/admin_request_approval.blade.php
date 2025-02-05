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
    <div class ="form-container">
        <div class="form-group">
                <label>名前</label>
                <div class="time-range">{{ $user->name }}</div>
        </div>
        <div class="form-group">
            <label>日付</label>
            <div class="form-group_content">
                <div class="form-group_content-detail">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y') }}年</div>
                <div class="form-group_content-detail">{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</div>
            </div>
        </div>
        <div class="form-group">
            <label>出勤・退勤</label>
            <div class="form-group_content">
                <div class="form-group_content-detail">{{ 
                    $isPending 
                        ? ($modRequest && $modRequest->requested_clock_in 
                            ? \Carbon\Carbon::parse($modRequest->requested_clock_in)->format('H:i')
                            : '--')
                        : \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')
                }}</div>
                <span>～</span>
                <div class="form-group_content-detail">{{ 
                    $isPending 
                        ? ($modRequest && $modRequest->requested_clock_out 
                            ? \Carbon\Carbon::parse($modRequest->requested_clock_out)->format('H:i')
                            : '--')
                        : \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')
                }}</div>
            </div>
        </div>
        @forelse($breakModRequests as $index => $breakModRequest)
        <div class="form-group">
            <label>
                @if ($loop->index === 0)
                    休憩
                @else
                    休憩{{ $loop->index + 1 }}
                @endif
            </label>
            <div class="form-group_content">
                <div class="form-group_content-detail">{{ \Carbon\Carbon::parse($breakModRequest->requested_break_start)->format('H:i') }}</div>
                <span>～</span>
                <div class="form-group_content-detail">{{ \Carbon\Carbon::parse($breakModRequest->requested_break_end)->format('H:i') }}</div>
            </div>
        </div>
        @empty
            <!-- 休憩時間がない場合は何も表示しない -->
        @endforelse
        <div class="form-group">
            <label>備考</label>
            <div class="form-text">
                <div class="form-group_content-detail">{{ $modRequest->reason}}</div>
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
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
    <form class="form-container" action="{{ route('attendance.mod_request') }}" method="POST">
        @csrf
        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
        <div class="{{ $isPending ? 'form-disabled' : 'form-active' }}">
            <div class="form-group">
                <label>名前</label>
                <div class="time-range">
                    <span class="user-name">{{ $attendance->user->name }}</span>
                </div>
            </div>
            <div class="form-group">
                <label>日付</label>
                <div class="time-range">
                    <input type="text" 
                        name="requested_year" 
                        value="{{ rtrim(old('requested_year', 
                                        $modRequest ? \Carbon\Carbon::parse($modRequest->requested_work_date)->format('Y') : 
                                        \Carbon\Carbon::parse($attendance->work_date)->format('Y')), '年') }}年">
                    <input type="text" 
                        name="requested_date" 
                        value="{{ old('requested_date', 
                                        $modRequest ? \Carbon\Carbon::parse($modRequest->requested_work_date)->format('n月j日') : 
                                        \Carbon\Carbon::parse($attendance->work_date)->format('n月j日')) }}">
                </div>
            </div>
            <div class="form-group">
                <label>出勤・退勤</label>
                <div class="time-range">
                    <input type="text" name="requested_clock_in" value="{{ old('requested_clock_in',
                          $isPending && $modRequest
                            ? \Carbon\Carbon::parse($modRequest->requested_clock_in)->format('H:i')
                            : \Carbon\Carbon::parse($attendance->clock_in)->format('H:i'))
                    }}">
                    <span>～</span>
                    <input type="text" name="requested_clock_out" value="{{ old('requested_clock_out',
                          $isPending && $modRequest
                            ? \Carbon\Carbon::parse($modRequest->requested_clock_out)->format('H:i')
                            : \Carbon\Carbon::parse($attendance->clock_out)->format('H:i'))
                    }}">
                </div>
            </div>
            @foreach ($attendance->break_times as $index => $break_time)
            <div class="form-group__break">
                <label>
                    @if ($index === 0)
                        休憩
                    @else
                        休憩{{ $index + 1 }}
                    @endif
                </label>
                <div class="time-range">
                    <input type="hidden" name="break_times[{{$index}}][id]" value="{{ $break_time->id }}">
                    <input type="text" name="break_times[{{$index}}][requested_break_start]" value="{{ old('break_times.'.$index.'.requested_break_start', 
                    $isPending && isset($breakModRequests[$break_time->id])
                        ? ($breakModRequests[$break_time->id]->requested_break_start 
                            ? \Carbon\Carbon::parse($breakModRequests[$break_time->id]->requested_break_start)->format('H:i') 
                            : '-')
                        : ($break_time->break_start 
                            ? \Carbon\Carbon::parse($break_time->break_start)->format('H:i') 
                            : '-')) 
                }}">
                    <span>～</span>
                    <input type="text" name="break_times[{{$index}}][requested_break_end]" value="{{ old('break_times.'.$index.'.requested_break_end',
                    $isPending && isset($breakModRequests[$break_time->id])
                        ? ($breakModRequests[$break_time->id]->requested_break_end 
                            ? \Carbon\Carbon::parse($breakModRequests[$break_time->id]->requested_break_end)->format('H:i') 
                            : '-')
                        : ($break_time->break_end 
                            ? \Carbon\Carbon::parse($break_time->break_end)->format('H:i') 
                            : '-'))
                }}">
                </div>
            </div>
            @endforeach
            <div class="form-group__break">
                <label>休憩{{ count($attendance->break_times) + 1 }}</label>
                <div class="time-range">
                    <input type="text" name="break_times[{{ count($attendance->break_times) }}][requested_break_start]" value="{{ old('break_times.'.count($attendance->break_times).'.requested_break_start', '') }}">
                    <span>～</span>
                    <input type="text" name="break_times[{{ count($attendance->break_times) }}][requested_break_end]" value="{{ old('break_times.'.count($attendance->break_times).'.requested_break_end', '') }}">
                </div>
            </div>
            <div class="form-group">
                <label>備考</label>
                <div class="form-text">
                    <textarea class="form-text-content" name="reason">{{ old('reason', $isPending && $modRequest ? $modRequest->reason : '') }}</textarea>
                </div>
            </div>
            @if ($errors->any())
                <div class="error-messages">
                    @foreach ($errors->all() as $error)
                        <div class="error-message">{{ $error }}</div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="button-container">
            @if ($isPending)
                <span class="pending-message">*承認待ちのため修正できません。</span>
            @else
                <button class="submit-btn">修正</button>
            @endif
        </div>
    </form>
</main>
@endsection
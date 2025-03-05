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
    <form class="form-container" action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
        <div class="{{ ($isPending || ($modRequest && $modRequest->status == 'approved')) ? 'form-disabled' : 'form-active' }}">
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
                    ? ($modRequest->requested_clock_in
                    ? \Carbon\Carbon::parse($modRequest->requested_clock_in)->format('H:i')
                    : '-')
                    : ($attendance->clock_in
                    ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')
                    : '-'))
                    }}">
                    <span>～</span>
                    <input type="text" name="requested_clock_out" value="{{ old('requested_clock_out',
                    $isPending && $modRequest
                    ? ($modRequest->requested_clock_out
                    ? \Carbon\Carbon::parse($modRequest->requested_clock_out)->format('H:i')
                    : '-')
                    : ($attendance->clock_out
                    ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')
                    : '-'))
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
                    <input type="text" name="break_times[{{$index}}][requested_break_start]" value="{{
                        old('break_times.'.$index.'.requested_break_start',
                            $isPending && ($breakModRequest = $breakModRequests->firstWhere('temp_index', $index))
                                ? ($breakModRequest->requested_break_start
                                    ? \Carbon\Carbon::parse($breakModRequest->requested_break_start)->format('H:i')
                                    : '-')
                                : ($break_time->break_start
                                    ? \Carbon\Carbon::parse($break_time->break_start)->format('H:i')
                                    : '-')
                        )
                    }}">
                    <span>～</span>
                    <input type="text" name="break_times[{{$index}}][requested_break_end]" value="{{
                    old('break_times.'.$index.'.requested_break_end',
                        $isPending && ($breakModRequest = $breakModRequests->firstWhere('temp_index', $index))
                            ? ($breakModRequest->requested_break_end
                                ? \Carbon\Carbon::parse($breakModRequest->requested_break_end)->format('H:i')
                                : '-')
                            : ($break_time->break_end
                                ? \Carbon\Carbon::parse($break_time->break_end)->format('H:i')
                                : '-')
                    )
                }}">
                </div>
            </div>
            @endforeach
            <div class="form-group__break">
                <label>休憩{{ count($attendance->break_times) + 1 }}</label>
                <div class="time-range">
                    <input type="text" name="break_times[{{ count($attendance->break_times) }}][requested_break_start]" value="{{
                        old('break_times.'.count($attendance->break_times).'.requested_break_start',
                            $isPending && ($newBreakRequest = $breakModRequests->where('break_times_id', null)->first())
                                ? ($newBreakRequest->requested_break_start
                                    ? \Carbon\Carbon::parse($newBreakRequest->requested_break_start)->format('H:i')
                                    : '-')
                                : ''
                        )
                    }}">
                    <span>～</span>
                    <input type="text" name="break_times[{{ count($attendance->break_times) }}][requested_break_end]" value="{{
                        old('break_times.'.count($attendance->break_times).'.requested_break_end',
                            $isPending && ($newBreakRequest = $breakModRequests->where('break_times_id', null)->first())
                                ? ($newBreakRequest->requested_break_end
                                    ? \Carbon\Carbon::parse($newBreakRequest->requested_break_end)->format('H:i')
                                    : '-')
                                : ''
                        )
                    }}">
                </div>
            </div>
            <div class="form-group">
                <label>備考</label>
                <div class="form-text">
                    <textarea class="form-text-content" name="reason">{{ old('reason', $modRequest ? $modRequest->reason : '') }}</textarea>
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
            @elseif (!$isPending && $modRequest && $modRequest->status == \App\Models\AttendanceModification::STATUS_APPROVED)
                <span class="submit-btn-disabled" disabled>承認済み
                </span>
            @else
                <button class="submit-btn">修正</button>
            @endif
        </div>
    </form>
</main>
@endsection
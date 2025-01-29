@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('content')
<main class="container center">
    <div class="attendance__list">
        <div class="vertical-bar"></div>
        <h1 class="heading-text">申請一覧</h1>
    </div>
    <div class="tabs">
        <a href="{{ url('stamp-correction/list?tab=pending') }}" class="tab {{ $tab === 'pending' ? 'active' : '' }}">承認待ち</a>
        <a href="{{ url('stamp-correction/list?tab=approved') }}" class="tab {{ $tab === 'approved' ? 'active' : '' }}">承認済み</a>
    </div>
    <div class="table-container">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $request)
                <tr>
                    <td>{{ $request->status_label }}</td>
                    <td>{{ $request->attendance->user->name }}</td>
                    <td>{{ $request->requested_clock_in->format('Y/m/d') }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    <td><a href="{{ route('attendance.show', $request->attendance_id) }}" class="detail-link">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</main>
@endsection
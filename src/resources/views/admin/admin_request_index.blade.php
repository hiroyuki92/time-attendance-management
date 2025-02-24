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
        <a href="{{ url('admin/stamp_correction_request/list?tab=pending') }}" class="tab {{ $tab === 'pending' ? 'active' : '' }}">承認待ち</a>
        <a href="{{ url('admin/stamp_correction_request/list?tab=approved') }}" class="tab {{ $tab === 'approved' ? 'active' : '' }}">承認済み</a>
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
                @foreach ($requests as $modRequest)
                <tr>
                    <td>{{ $modRequest->status_label }}</td>
                    <td>{{ $modRequest->attendance->user->name }}</td>
                    <td>{{ $modRequest->attendance->work_date->format('Y/m/d') }}</td>
                    <td>{{ $modRequest->reason }}</td>
                    <td>{{ $modRequest->created_at->format('Y/m/d') }}</td>
                    <td><a href="{{ route('admin.requests.show', ['attendance_correct_request' => $modRequest->id]) }}" class="detail-link">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</main>
@endsection
@extends('layouts.app')
@section('page-title', 'Employee QR Code')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card text-center">
            <div class="card-header p-3">
                Personal QR Code — {{ $employee->name }}
            </div>
            <div class="card-body py-4">

                {{-- QR Code image --}}
                <img src="data:{{ $qrMimeType }};base64,{{ $qrBase64 }}"
                    alt="QR Code for {{ $employee->name }}"
                    class="img-fluid mb-3"
                    style="max-width: 260px; border-radius: 12px;">

                <p class="fw-semibold fs-5 mb-1">{{ $employee->name }}</p>
                <p class="text-muted mb-0">{{ $employee->employee_id }}</p>
                <p class="text-muted small mb-3">{{ $employee->department ?? '' }}</p>

                <div class="alert alert-warning py-2 small text-start mb-3">
                    <i class="bi bi-shield-lock"></i>
                    <strong>This is a personal QR.</strong> Each employee has a unique one.
                    Do not share it — it is used to identify who is checking in.
                </div>

                <div class="alert alert-info py-2 small text-start mb-4">
                    <strong>How to use:</strong><br>
                    1. Employee keeps this QR card with them<br>
                    2. When arriving, scan the <strong>Location QR</strong> at the door<br>
                    3. On the scan page, enter or scan this personal QR token<br>
                    4. GPS is verified automatically
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('employees.download-qr', $employee) }}"
                        class="btn btn-primary flex-fill">
                        <i class="bi bi-download"></i> Download QR
                    </a>
                    <a href="{{ route('employees.index') }}"
                        class="btn btn-outline-secondary">← Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
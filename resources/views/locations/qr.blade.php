@extends('layouts.app')
@section('page-title','Location QR')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card text-center">
            <div class="card-header p-3">QR Code — {{ $location->name }}</div>
            <div class="card-body py-4">
                <img src="data:{{ $qrMimeType }};base64,{{ $qrBase64 }}"
                    class="img-fluid mb-3" style="max-width:260px;"
                    alt="QR Code for {{ $location->name }}">
                <p class="fw-semibold mb-1">{{ $location->name }}</p>
                <p class="text-muted small">{{ $location->address ?? '' }}</p>
                <p class="text-muted small">Radius: {{ $location->radius_meters }}m</p>
                <div class="alert alert-info py-2 small text-start">
                    <strong>How to use:</strong><br>
                    1. Print this QR and place it at the entrance<br>
                    2. Employee scans with phone camera<br>
                    3. They enter their personal QR token<br>
                    4. System verifies they are within {{ $location->radius_meters }}m<br>
                    5. Telegram notification is sent
                </div>
                <a href="{{ route('locations.download-qr', $location) }}"
                    class="btn btn-primary w-100">
                    <i class="bi bi-download"></i> Download QR
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
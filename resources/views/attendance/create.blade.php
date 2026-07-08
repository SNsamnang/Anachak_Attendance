@extends('layouts.app')
@section('page-title', 'Add Attendance Record')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header p-3 d-flex justify-content-between align-items-center">
                <span>➕ Add Attendance Record</span>
                <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary">
                    ← Back
                </a>
            </div>
            <div class="card-body p-4">

                <div class="alert alert-info small py-2 mb-4">
                    <i class="bi bi-info-circle"></i>
                    Records added manually are marked as <strong>Manual</strong> and
                    status / OT are auto-calculated from the employee's work hours.
                </div>

                @if($errors->any())
                <div class="alert alert-danger small py-2 mb-3">
                    {{ $errors->first() }}
                </div>
                @endif

                <form method="POST" action="{{ route('attendance.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Employee <span class="text-danger">*</span>
                        </label>
                        <select name="employee_id"
                            class="form-select @error('employee_id') is-invalid @enderror"
                            required>
                            <option value="">— Select employee —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                @selected(old('employee_id')==$emp->id)>
                                {{ $emp->name }} ({{ $emp->employee_id }})
                                — ⏱ {{ $emp->workStartFormatted() }}–{{ $emp->workEndFormatted() }}
                            </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Location <span class="text-danger">*</span>
                        </label>
                        <select name="location_id"
                            class="form-select @error('location_id') is-invalid @enderror"
                            required>
                            <option value="">— Select location —</option>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}"
                                @selected(old('location_id')==$loc->id)>
                                {{ $loc->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('location_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Type <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                    name="type" value="in" id="typeIn"
                                    {{ old('type', 'in') === 'in' ? 'checked' : '' }} required>
                                <label class="form-check-label text-success fw-semibold" for="typeIn">
                                    ✅ Check In
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                    name="type" value="out" id="typeOut"
                                    {{ old('type') === 'out' ? 'checked' : '' }}>
                                <label class="form-check-label text-danger fw-semibold" for="typeOut">
                                    🔴 Check Out
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Date &amp; Time <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" name="scanned_at"
                            class="form-control @error('scanned_at') is-invalid @enderror"
                            value="{{ old('scanned_at', now()->format('Y-m-d\TH:i')) }}"
                            required>
                        <div class="form-text">
                            Status (on time / late) and OT will be calculated automatically.
                        </div>
                        @error('scanned_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-check2"></i> Save Record
                        </button>
                        <a href="{{ route('attendance.index') }}"
                            class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
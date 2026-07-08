@extends('layouts.app')
@section('page-title', 'Edit Attendance Record')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header p-3 d-flex justify-content-between align-items-center">
                <span>✏️ Edit Attendance Record</span>
                <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary">
                    ← Back
                </a>
            </div>
            <div class="card-body p-4">

                {{-- Current record info --}}
                <div class="alert alert-secondary small py-2 mb-4">
                    <strong>Current record:</strong>
                    {{ $attendance->employee->name }}
                    · {{ $attendance->type === 'in' ? '✅ Check In' : '🔴 Check Out' }}
                    · {{ $attendance->scanned_at->format('H:i · d M Y') }}
                    @if($attendance->ip_address === 'admin')
                    <span class="text-primary ms-1">✏️ Manual entry</span>
                    @endif
                </div>

                @if($errors->any())
                <div class="alert alert-danger small py-2 mb-3">
                    {{ $errors->first() }}
                </div>
                @endif

                <form method="POST" action="{{ route('attendance.update', $attendance) }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Employee</label>
                        <select name="employee_id"
                            class="form-select @error('employee_id') is-invalid @enderror"
                            required>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                @selected(old('employee_id', $attendance->employee_id) == $emp->id)>
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
                        <label class="form-label fw-semibold">Location</label>
                        <select name="location_id"
                            class="form-select @error('location_id') is-invalid @enderror"
                            required>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}"
                                @selected(old('location_id', $attendance->location_id) == $loc->id)>
                                {{ $loc->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('location_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                    name="type" value="in" id="typeIn"
                                    {{ old('type', $attendance->type) === 'in' ? 'checked' : '' }}>
                                <label class="form-check-label text-success fw-semibold" for="typeIn">
                                    ✅ Check In
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                    name="type" value="out" id="typeOut"
                                    {{ old('type', $attendance->type) === 'out' ? 'checked' : '' }}>
                                <label class="form-check-label text-danger fw-semibold" for="typeOut">
                                    🔴 Check Out
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Date &amp; Time</label>
                        <input type="datetime-local" name="scanned_at"
                            class="form-control @error('scanned_at') is-invalid @enderror"
                            value="{{ old('scanned_at', $attendance->scanned_at->format('Y-m-d\TH:i')) }}"
                            required>
                        <div class="form-text">
                            Status and OT will be recalculated automatically on save.
                        </div>
                        @error('scanned_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning flex-fill text-white fw-semibold">
                            <i class="bi bi-check2"></i> Update Record
                        </button>
                        <a href="{{ route('attendance.index') }}"
                            class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

                {{-- Delete section --}}
                <hr class="mt-4">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Permanently delete this record?</small>
                    <form method="POST" action="{{ route('attendance.destroy', $attendance) }}"
                        onsubmit="return confirm('Are you sure you want to delete this attendance record for {{ $attendance->employee->name }}? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
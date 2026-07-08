@extends('layouts.app')
@section('page-title', 'Edit Employee')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header p-3">
                Edit: {{ $employee->name }}
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('employees.update', $employee) }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $employee->name) }}"
                            required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Employee ID</label>
                        <input type="text" class="form-control bg-light"
                            value="{{ $employee->employee_id }}" disabled>
                        <div class="form-text text-muted">Employee ID cannot be changed.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department</label>
                        <input type="text" name="department"
                            class="form-control"
                            value="{{ old('department', $employee->department) }}"
                            placeholder="Engineering">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone"
                            class="form-control"
                            value="{{ old('phone', $employee->phone) }}"
                            placeholder="+855 12 345 678">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Salary</label>
                        <input type="number" name="salary" step="0.01"
                            class="form-control"
                            value="{{ old('salary', $employee->salary) }}"
                            placeholder="0.00">
                    </div>
                    {{-- Add this block after the Phone field --}}
                    <div class="row g-2 mb-4">
                        <div class="col">
                            <label class="form-label fw-semibold">Work Start Time</label>
                            <input type="time" name="work_start" class="form-control"
                                value="{{ old('work_start', substr($employee->work_start, 0, 5)) }}" required>
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold">Work End Time</label>
                            <input type="time" name="work_end" class="form-control"
                                value="{{ old('work_end', substr($employee->work_end, 0, 5)) }}" required>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-check2"></i> Save Changes
                        </button>
                        <a href="{{ route('employees.index') }}"
                            class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
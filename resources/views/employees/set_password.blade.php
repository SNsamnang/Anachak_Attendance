@extends('layouts.app')
@section('page-title', 'Set Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header p-3">
                Set Portal Password — {{ $employee->name }}
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle"></i>
                    Set a password so <strong>{{ $employee->name }}</strong>
                    can log in to the employee portal at
                    <a href="{{ route('portal.login') }}" target="_blank">
                        /portal/login
                    </a>
                    using Employee ID: <strong>{{ $employee->employee_id }}</strong>
                </div>

                <form method="POST" action="{{ route('employees.set-password.post', $employee) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Minimum 6 characters" required>
                        @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirm Password</label>
                        <input type="password" name="password_confirmation"
                            class="form-control" placeholder="Repeat password" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-key"></i> Save Password
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
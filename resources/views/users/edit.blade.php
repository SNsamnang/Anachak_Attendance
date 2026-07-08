@extends('layouts.app')
@section('page-title','Edit User')
@section('content')

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">← Back</a>
    <h6 class="mb-0 fw-semibold">Edit User — {{ $user->name }}</h6>
</div>

<div class="card" style="max-width:520px">
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                <select name="role_id" class="form-select" required>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Company</label>
                <select name="company_id" class="form-select">
                    <option value="">— No company (Super Admin) —</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected(old('company_id', $user->company_id) == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <div class="form-text">Assign a company so this user only sees that company's data.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">New Password <span class="text-muted fw-normal">(leave blank to keep current)</span></label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>
@endsection

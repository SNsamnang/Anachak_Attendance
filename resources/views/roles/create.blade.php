@extends('layouts.app')
@section('page-title','New Role')
@section('content')

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">← Back</a>
    <h6 class="mb-0 fw-semibold">Create Role</h6>
</div>

<div class="card" style="max-width:600px">
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('roles.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Slug <span class="text-danger">*</span>
                    <span class="fw-normal text-muted">(lowercase, dashes only)</span>
                </label>
                <input type="text" name="slug" class="form-control font-monospace" value="{{ old('slug') }}"
                    placeholder="e.g. hr-manager" required>
            </div>

            <label class="form-label fw-semibold mb-2">Permissions</label>
            @foreach($permissions as $group => $perms)
            <div class="mb-3">
                <div class="text-muted small fw-semibold mb-1 text-uppercase" style="letter-spacing:.5px">{{ $group }}</div>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($perms as $perm)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="permissions[]"
                            value="{{ $perm->id }}" id="p{{ $perm->id }}"
                            @checked(in_array($perm->id, old('permissions', [])))>
                        <label class="form-check-label" for="p{{ $perm->id }}">{{ $perm->name }}</label>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            <button type="submit" class="btn btn-primary mt-2">Create Role</button>
        </form>
    </div>
</div>
@endsection

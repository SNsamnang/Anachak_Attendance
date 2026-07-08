@extends('layouts.app')
@section('page-title','Edit Role')
@section('content')

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">← Back</a>
    <h6 class="mb-0 fw-semibold">Edit Role — {{ $role->name }}</h6>
</div>

<div class="card" style="max-width:600px">
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('roles.update', $role) }}">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $role->name) }}" required>
                <div class="form-text">Slug: <code>{{ $role->slug }}</code> (cannot be changed)</div>
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
                            @checked(in_array($perm->id, old('permissions', $rolePerms)))>
                        <label class="form-check-label" for="p{{ $perm->id }}">{{ $perm->name }}</label>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            <button type="submit" class="btn btn-primary mt-2">Save Changes</button>
        </form>
    </div>
</div>
@endsection

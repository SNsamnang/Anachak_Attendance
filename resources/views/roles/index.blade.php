@extends('layouts.app')
@section('page-title','Roles & Permissions')
@section('content')

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0 fw-semibold">Roles ({{ $roles->count() }})</h6>
    <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Role
    </a>
</div>

<div class="row g-3">
    @forelse($roles as $role)
    <div class="col-12 col-md-12">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-bold">{{ $role->name }}</div>
                        <small class="text-muted">slug: {{ $role->slug }} · {{ $role->users_count }} user{{ $role->users_count != 1 ? 's' : '' }}</small>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('roles.destroy', $role) }}" class="d-inline"
                            onsubmit="return confirm('Delete role {{ $role->name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-1 mt-2">
                    @foreach($role->permissions->sortBy('group') as $perm)
                    <span class="badge" style="background:#f1f5f9;color:#334155;font-size:11px">{{ $perm->name }}</span>
                    @endforeach
                    @if($role->permissions->isEmpty())
                    <span class="text-muted small">No permissions assigned</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12"><div class="card"><div class="card-body text-center text-muted">No roles found.</div></div></div>
    @endforelse
</div>
@endsection

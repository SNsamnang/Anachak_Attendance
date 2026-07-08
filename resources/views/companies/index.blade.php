@extends('layouts.app')
@section('page-title','Companies')
@section('content')

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0 fw-semibold">Companies ({{ $companies->count() }})</h6>
    <a href="{{ route('companies.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Company
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Code</th>
                    <th>Phone</th>
                    <th>Employees</th>
                    <th>Users</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $company)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $company->name }}</div>
                        @if($company->address)<div class="text-muted small">{{ $company->address }}</div>@endif
                    </td>
                    <td><code>{{ $company->code ?? '—' }}</code></td>
                    <td>{{ $company->phone ?? '—' }}</td>
                    <td>{{ $company->employees_count }}</td>
                    <td>{{ $company->users_count }}</td>
                    <td>
                        @if($company->is_active)
                            <span class="badge" style="background:#dcfce7;color:#166534">Active</span>
                        @else
                            <span class="badge" style="background:#fee2e2;color:#991b1b">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('companies.destroy', $company) }}" class="d-inline"
                            onsubmit="return confirm('Delete {{ $company->name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No companies found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

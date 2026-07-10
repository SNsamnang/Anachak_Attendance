@extends('layouts.app')
@section('page-title', 'Add Employee')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header p-3">Add New Employee</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('employees.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Full Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}"
                            placeholder="Sophea Chan"
                            required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Employee ID</label>
                        <div class="form-control bg-light text-muted" style="cursor:default">
                            <i class="bi bi-magic me-1"></i> Auto-generated from company code
                            <span class="ms-1 text-muted small">(e.g. ACME001)</span>
                        </div>
                    </div>

                    @if(auth()->user()->is_super_admin && $companies->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Company</label>
                        <select name="company_id" class="form-select">
                            <option value="">— No company —</option>
                            @foreach($companies as $c)
                            <option value="{{ $c->id }}" @selected(old('company_id') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department</label>
                        <input type="text" name="department"
                            class="form-control"
                            value="{{ old('department') }}"
                            placeholder="Engineering">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone"
                            class="form-control"
                            value="{{ old('phone', '+855 12 345 678') }}"
                            placeholder="+855 12 345 678">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Salary</label>
                        <input type="number" name="salary" step="0.01"
                            class="form-control"
                            value="{{ old('salary', '100') }}"
                            placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sessions per Day</label>
                        <select name="sessions" id="sessionsSelect" class="form-select">
                            <option value="1" @selected(old('sessions', 1) == 1)>1 Session (default)</option>
                            <option value="2" @selected(old('sessions', 1) == 2)>2 Sessions</option>
                        </select>
                        <div class="form-text">2 sessions = morning &amp; afternoon with a break in between.</div>
                    </div>

                    <div class="p-3 rounded mb-3" style="background:#f8f9fa;border:1px solid #dee2e6">
                        <div class="fw-semibold small text-muted mb-2" id="session1Label">SESSION 1</div>
                        <div class="row g-2">
                            <div class="col">
                                <label class="form-label fw-semibold">Start Time</label>
                                <input type="time" name="work_start" class="form-control"
                                    value="{{ old('work_start', '08:00') }}" required>
                            </div>
                            <div class="col">
                                <label class="form-label fw-semibold">End Time</label>
                                <input type="time" name="work_end" class="form-control"
                                    value="{{ old('work_end', '17:00') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded mb-4" id="session2Block"
                        style="background:#f0f7ff;border:1px solid #b6d4fe;display:none">
                        <div class="fw-semibold small text-muted mb-2">SESSION 2</div>
                        <div class="row g-2">
                            <div class="col">
                                <label class="form-label fw-semibold">Start Time</label>
                                <input type="time" name="session2_start" class="form-control"
                                    value="{{ old('session2_start', '13:00') }}">
                            </div>
                            <div class="col">
                                <label class="form-label fw-semibold">End Time</label>
                                <input type="time" name="session2_end" class="form-control"
                                    value="{{ old('session2_end', '17:00') }}">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-check2"></i> Save Employee
                        </button>
                        <a href="{{ route('employees.index') }}"
                            class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body py-3 small text-muted">
                <i class="bi bi-info-circle"></i>
                After saving, a unique QR token is automatically generated.
                Go to <strong>View QR</strong> to download and print the employee's personal QR card.
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const sel = document.getElementById('sessionsSelect');
    const blk = document.getElementById('session2Block');
    const lbl = document.getElementById('session1Label');
    function toggle() {
        const two = sel.value === '2';
        blk.style.display = two ? '' : 'none';
        lbl.style.display  = two ? '' : 'none';
    }
    sel.addEventListener('change', toggle);
    toggle();
})();
</script>
@endpush
@endsection
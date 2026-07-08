<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Leave Request — {{ $employee->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }

        .topbar {
            background: #1e293b; border-bottom: 1px solid #334155;
            padding: 0 20px; height: 60px; display: flex;
            align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .topbar-left { display: flex; align-items: center; gap: 10px; }
        .brand-icon { width: 34px; height: 34px; background: #10b981; border-radius: 9px; display: grid; place-items: center; font-size: 16px; }
        .topbar-name { font-weight: 700; font-size: 14px; color: #f1f5f9; }
        .topbar-sub  { font-size: 11px; color: #94a3b8; }
        .btn-back {
            background: #334155; border: none; color: #94a3b8; font-size: 13px;
            padding: 7px 14px; border-radius: 8px; cursor: pointer; text-decoration: none;
            display: flex; align-items: center; gap: 6px; transition: background .15s, color .15s;
        }
        .btn-back:hover { background: #475569; color: #f1f5f9; }

        .page-body { padding: 24px 16px; max-width: 680px; margin: 0 auto; }

        .form-card {
            background: #1e293b; border: 1px solid #334155;
            border-radius: 14px; padding: 24px; margin-bottom: 20px;
        }
        .form-card-title {
            font-size: 15px; font-weight: 700; color: #f1f5f9; margin-bottom: 18px;
            display: flex; align-items: center; gap: 8px;
        }
        .form-card-title i { color: #10b981; font-size: 18px; }

        .lbl { font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 5px; display: block; }
        .inp {
            background: #0f172a; border: 1px solid #334155; color: #e2e8f0;
            border-radius: 8px; font-size: 14px; padding: 9px 12px;
            width: 100%; outline: none; transition: border-color .15s;
            font-family: 'Inter', system-ui, sans-serif;
        }
        .inp:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.15); background: #0f172a; color: #e2e8f0; }
        .inp option  { background: #1e293b; }
        .inp::placeholder { color: #64748b; }

        /* Type toggle */
        .type-toggle { display: flex; gap: 8px; margin-bottom: 4px; }
        .type-btn {
            flex: 1; padding: 10px; border-radius: 10px; border: 2px solid #334155;
            background: #0f172a; color: #94a3b8; font-size: 13px; font-weight: 600;
            cursor: pointer; text-align: center; transition: all .15s; user-select: none;
        }
        .type-btn.active { border-color: #10b981; background: rgba(16,185,129,.1); color: #10b981; }
        .type-btn i { display: block; font-size: 20px; margin-bottom: 4px; }

        .btn-submit {
            background: #10b981; border: none; color: #fff; font-size: 14px; font-weight: 600;
            padding: 11px 24px; border-radius: 10px; cursor: pointer; width: 100%; transition: background .15s;
        }
        .btn-submit:hover { background: #059669; }

        /* Request history cards */
        .req-item {
            background: #1e293b; border: 1px solid #334155; border-radius: 12px;
            padding: 14px 16px; margin-bottom: 10px;
        }
        .req-head { display: flex; justify-content: space-between; align-items: flex-start; }
        .req-title { font-weight: 700; font-size: 13.5px; color: #f1f5f9; }
        .req-sub   { font-size: 12px; color: #64748b; margin-top: 1px; }
        .bdg { font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 6px; white-space: nowrap; }
        .bdg-pending  { background: #422006; color: #fbbf24; }
        .bdg-approved { background: #052e16; color: #4ade80; }
        .bdg-rejected { background: #450a0a; color: #f87171; }
        .bdg-dayoff   { background: #2e1065; color: #c4b5fd; }
        .bdg-timeoff  { background: #0c4a6e; color: #7dd3fc; }

        .alert-ok  { background: #052e16; border: 1px solid #166534; color: #4ade80; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px; }
        .alert-err { background: #450a0a; border: 1px solid #991b1b; color: #f87171; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px; }

        .section-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; margin-bottom: 12px; }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-left">
        <div class="brand-icon">📋</div>
        <div>
            <div class="topbar-name">{{ $employee->name }}</div>
            <div class="topbar-sub">{{ $employee->employee_id }}</div>
        </div>
    </div>
    <a href="{{ route('portal.dashboard') }}" class="btn-back">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</header>

<div class="page-body">

    @if(session('success'))
    <div class="alert-ok"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert-err">
        @foreach($errors->all() as $e)<div><i class="bi bi-exclamation-circle me-1"></i>{{ $e }}</div>@endforeach
    </div>
    @endif

    {{-- Submit form --}}
    <div class="form-card">
        <div class="form-card-title">
            <i class="bi bi-calendar-plus"></i> New Leave Request
        </div>
        <form method="POST" action="{{ route('portal.leave-request.store') }}" id="leaveForm">
            @csrf

            {{-- Type toggle --}}
            <div style="margin-bottom:18px">
                <label class="lbl">Request Type</label>
                <div class="type-toggle">
                    <div class="type-btn {{ old('type','day_off')==='day_off' ? 'active' : '' }}"
                         id="btn-dayoff" onclick="setType('day_off')">
                        <i class="bi bi-calendar-x"></i>
                        Day Off
                    </div>
                    <div class="type-btn {{ old('type')==='time_off' ? 'active' : '' }}"
                         id="btn-timeoff" onclick="setType('time_off')">
                        <i class="bi bi-clock-history"></i>
                        Time Off
                    </div>
                </div>
                <input type="hidden" name="type" id="typeInput" value="{{ old('type','day_off') }}">
            </div>

            {{-- Day Off fields --}}
            <div id="fields-dayoff" style="{{ old('type')==='time_off' ? 'display:none' : '' }}">
                <div class="row g-3" style="margin-bottom:0">
                    <div class="col-6">
                        <label class="lbl">From Date <span style="color:#f87171">*</span></label>
                        <input type="date" name="leave_date" class="inp"
                            value="{{ old('leave_date', today()->toDateString()) }}" required>
                    </div>
                    <div class="col-6">
                        <label class="lbl">To Date <span style="color:#64748b;font-weight:400">(optional)</span></label>
                        <input type="date" name="leave_date_end" class="inp"
                            value="{{ old('leave_date_end') }}">
                    </div>
                </div>
            </div>

            {{-- Time Off fields --}}
            <div id="fields-timeoff" style="{{ old('type')==='time_off' ? '' : 'display:none' }}">
                <div class="row g-3" style="margin-bottom:0">
                    <div class="col-12 col-sm-4">
                        <label class="lbl">Date <span style="color:#f87171">*</span></label>
                        <input type="date" name="leave_date_time" class="inp"
                            value="{{ old('leave_date_time', today()->toDateString()) }}">
                    </div>
                    <div class="col-6 col-sm-4">
                        <label class="lbl">From Time <span style="color:#f87171">*</span></label>
                        <input type="time" name="start_time" class="inp" value="{{ old('start_time') }}">
                    </div>
                    <div class="col-6 col-sm-4">
                        <label class="lbl">To Time <span style="color:#f87171">*</span></label>
                        <input type="time" name="end_time" class="inp" value="{{ old('end_time') }}">
                    </div>
                </div>
            </div>

            <div style="margin-top:14px;margin-bottom:18px">
                <label class="lbl">Reason <span style="color:#f87171">*</span></label>
                <textarea name="reason" class="inp" rows="2"
                    placeholder="Why are you requesting leave?"
                    style="resize:vertical" required>{{ old('reason') }}</textarea>
            </div>

            <button type="submit" class="btn-submit">
                <i class="bi bi-send me-2"></i>Submit Request
            </button>
        </form>
    </div>

    {{-- My request history --}}
    <div class="section-label">My Requests</div>

    @forelse($myRequests as $req)
    <div class="req-item">
        <div class="req-head">
            <div>
                <div class="req-title">
                    @if($req->type === 'day_off')
                    <span class="bdg bdg-dayoff me-1">Day Off</span>
                    @else
                    <span class="bdg bdg-timeoff me-1">Time Off</span>
                    @endif
                    {{ $req->durationLabel() }}
                </div>
                <div class="req-sub">{{ $req->created_at->format('d M Y H:i') }}</div>
            </div>
            <span class="bdg bdg-{{ $req->status }}">{{ ucfirst($req->status) }}</span>
        </div>
        @if($req->reason)
        <div style="font-size:12px;color:#94a3b8;margin-top:6px">
            <i class="bi bi-chat-left-text me-1"></i>{{ $req->reason }}
        </div>
        @endif
        @if($req->admin_note)
        <div style="font-size:12px;margin-top:4px;color:{{ $req->status==='rejected' ? '#f87171' : '#4ade80' }}">
            <i class="bi bi-reply me-1"></i>{{ $req->admin_note }}
        </div>
        @endif
    </div>
    @empty
    <div class="form-card" style="text-align:center;color:#64748b;padding:32px">
        <i class="bi bi-inbox" style="font-size:2rem;opacity:.4;display:block;margin-bottom:8px"></i>
        No requests submitted yet.
    </div>
    @endforelse

</div>

<script>
function setType(t) {
    document.getElementById('typeInput').value = t;
    document.getElementById('btn-dayoff').classList.toggle('active', t === 'day_off');
    document.getElementById('btn-timeoff').classList.toggle('active', t === 'time_off');
    document.getElementById('fields-dayoff').style.display  = t === 'day_off'  ? '' : 'none';
    document.getElementById('fields-timeoff').style.display = t === 'time_off' ? '' : 'none';
}

// Copy date for time_off to a shared field on submit
document.getElementById('leaveForm').addEventListener('submit', function() {
    const t = document.getElementById('typeInput').value;
    if (t === 'time_off') {
        // copy leave_date_time → leave_date
        const d = document.querySelector('[name="leave_date_time"]').value;
        const hidden = document.createElement('input');
        hidden.type  = 'hidden';
        hidden.name  = 'leave_date';
        hidden.value = d;
        this.appendChild(hidden);
    }
});
</script>
</body>
</html>

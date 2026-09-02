@extends('layouts.app')
@section('title', 'Visitor Pass Management')

@section('content')
<div class="hero-govt flex items-center gap-5">
    <div class="hero-logo-badge hidden sm:flex">
        <img src="{{ asset('images/lsb-icon.png') }}" alt="">
    </div>
    <div>
        <p class="eyebrow">Perimeter Security Group &middot; Pass registry</p>
        <h1>Visitor Pass Management</h1>
        <p class="lead">5 passes per building - assign a visitor to issue and print a badge.</p>
    </div>
</div>

@if (session('success'))
    <div class="alert-govt-success" role="status">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert-govt-error" role="alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        {{ $errors->first() }}
    </div>
@endif

<div class="card-govt card-ribbon p-5 shadow-sm flex flex-wrap justify-between items-center gap-4" style="--ribbon-color: var(--brand-blue)">
    <div>
        <span class="tag-pill tag-pill-blue">Registry</span>
        <h2 class="text-lg font-bold text-slate-800 mt-1">Visitor pass registry</h2>
    </div>
    <button onclick="document.getElementById('registerModal').classList.remove('hidden')" class="btn-govt-cta px-4 py-2 text-xs">
        <i class="fa-solid fa-user-plus"></i> Register visitor &amp; issue pass
    </button>
</div>

<div class="flex overflow-x-auto gap-2 pb-2">
    <a href="{{ route('passes.index') }}" class="pill-filter {{ !request('building') ? 'is-active' : '' }}">All buildings</a>
    @foreach ($buildings as $b)
        <a href="{{ route('passes.index', ['building' => $b->code]) }}" class="pill-filter flex items-center gap-1.5 {{ request('building') === $b->code ? 'is-active' : '' }}">
            <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $b->color_hex }}"></span> {{ $b->name }}
        </a>
    @endforeach
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach ($passes as $p)
        @php
            $cardColor = $p->is_multi_building ? 'var(--badge-multi)' : $p->building->color_hex;
            $buildingLabel = $p->is_multi_building ? 'Multiple Access' : $p->building->name;
            $footerLabel = $p->is_multi_building ? 'Multiple Access badge' : $p->building->color_name . ' badge';
        @endphp
        <div class="pass-card shadow-sm">
            <div class="p-4 pass-card-header flex justify-between items-start">
                <div>
                    <span class="pass-card-eyebrow">House of Reps</span>
                    <div class="pass-card-building">{{ $buildingLabel }}</div>
                    <div class="text-xs font-semibold text-slate-500">
                        @if ($p->is_multi_building)
    Multiple access to buildings: {{ $p->buildings->pluck('name')->join(', ') }}
@else
    Visitor pass
@endif
                    </div>
                </div>
                <span class="badge-status {{ $p->status === 'active' ? 'badge-authorized' : 'badge-info' }}">
                    {{ ucfirst($p->status) }}
                </span>
            </div>
            <div class="p-4 space-y-2 text-xs">
                <div class="flex justify-between bg-slate-50 p-2 rounded-lg font-mono">
                    <span class="text-slate-400">Pass #:</span>
                    <span class="font-bold text-slate-800 text-sm">{{ $p->pass_number }}</span>
                </div>
                <div>
                    <span class="eyebrow-label" style="color: var(--ink-muted); font-size: 0.65rem;">Visitor</span>
                    <div class="font-bold text-slate-800 truncate">{{ $p->visitor_name ?: 'Unassigned' }}</div>
                </div>
            </div>

            

<div class="px-4 py-2 pass-card-footer flex justify-between items-center" style="background:{{ $cardColor }}">
    <div class="text-xs font-bold">{{ $footerLabel }}</div>
    <div class="flex gap-1">
        @if ($p->visitor_name)
            <form method="POST" action="{{ route('passes.unassign', $p) }}"
                  onsubmit="return confirm('Unassign {{ $p->visitor_name }} from Pass #{{ $p->pass_number }}?{{ $p->is_multi_building ? ' This will permanently delete this Multiple Access pass.' : '' }}');">
                @csrf
                <button type="submit" class="bg-white/20 hover:bg-white/30 text-white px-2.5 py-1 rounded text-xs font-bold">
                    <i class="fa-solid fa-user-xmark"></i> Unassign
                </button>
            </form>
        @endif
        <a href="{{ route('passes.show', $p) }}" class="bg-white/20 hover:bg-white/30 text-white px-2.5 py-1 rounded text-xs font-bold">
            <i class="fa-solid fa-qrcode"></i> View QR
        </a>
    </div>
</div>




        </div>
    @endforeach
</div>

<div id="registerModal" class="fixed inset-0 bg-slate-900/60 z-50 hidden flex items-center justify-center p-4">
    <div class="modal-govt-panel shadow-2xl max-w-md w-full p-6 space-y-4">
        <h3 class="font-bold text-slate-800">Register visitor &amp; issue pass</h3>

        {{-- Pass type toggle --}}
        <div class="flex gap-2 text-xs">
            <label class="pass-type-option flex-1">
                <input type="radio" name="pass_type_radio" value="single" checked onchange="setPassType('single')" class="sr-only">
                <span class="pass-type-btn is-active" id="passTypeBtnSingle">Single-Building Pass</span>
            </label>
            <label class="pass-type-option flex-1">
                <input type="radio" name="pass_type_radio" value="multi" onchange="setPassType('multi')" class="sr-only">
                <span class="pass-type-btn" id="passTypeBtnMulti">Multiple Access Pass</span>
            </label>
        </div>

                <form method="POST" action="{{ route('passes.register') }}" class="space-y-3 text-xs" id="registerForm">
            @csrf
            <input type="hidden" name="pass_type" id="passTypeInput" value="single">

            <div>
                <label class="block mb-1">Visitor photo</label>
                <div id="photoCaptureArea">
                    <video id="photoVideo" autoplay playsinline class="w-full rounded border hidden"></video>
                    <canvas id="photoCanvas" class="hidden"></canvas>
                    <img id="photoPreview" class="w-full rounded border hidden" alt="Captured photo">

                    <div class="flex gap-2 mt-2">
                        <button type="button" id="startCameraBtn" onclick="startCamera()"
                                class="px-3 py-1.5 rounded bg-slate-700 text-white text-xs">
                            Open Camera
                        </button>
                        <button type="button" id="captureBtn" onclick="capturePhoto()"
                                class="px-3 py-1.5 rounded bg-red-600 text-white text-xs hidden">
                            Capture
                        </button>
                        <button type="button" id="retakeBtn" onclick="retakePhoto()"
                                class="px-3 py-1.5 rounded btn-govt-ghost text-xs hidden">
                            Retake
                        </button>
                    </div>
                </div>
                <input type="hidden" name="photo_data" id="photoDataInput">
            </div>

            <div>
                <label class="block mb-1">Visitor full name *</label>
                <input type="text" name="visitor_name" required class="w-full px-3 py-2">
            </div>
            <div>
                <label class="block mb-1">Government ID type *</label>
                <select name="id_type" required class="w-full px-3 py-2 bg-slate-50">
                    <option value="">Select ID type</option>
                    <option value="Driver's License">Driver's License</option>
                    <option value="UMID">UMID</option>
                    <option value="Passport">Passport</option>
                    <option value="SSS ID">SSS ID</option>
                    <option value="PhilHealth ID">PhilHealth ID</option>
                    <option value="PhilSys (National ID)">PhilSys (National ID)</option>
                    <option value="Company ID">Company ID</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div>
                <label class="block mb-1">ID number *</label>
                <input type="text" name="id_ref" required class="w-full px-3 py-2" placeholder="e.g. N01-23-456789">
            </div>
            <div>
                <label class="block mb-1">Purpose of visit *</label>
                <input type="text" name="purpose" required class="w-full px-3 py-2">
            </div>

            {{-- Single-building: pick a building, and the backend auto-assigns
                 the next available pass in that building. Only buildings with
                 at least one free pass are listed. --}}
            <div id="singlePassField">
                <label class="block mb-1">Select building *</label>
                <select name="building_id" id="buildingIdSelect" class="w-full px-3 py-2 bg-slate-50">
                    @foreach ($buildings->where('code', '!=', 'MULTI') as $b)
                        @php
                            $available = $passes->where('building_id', $b->id)->where('is_multi_building', false)->whereNull('visitor_name')->count();
                        @endphp
                        @if ($available > 0)
                            <option value="{{ $b->id }}">{{ $b->name }} ({{ $available }} available)</option>
                        @endif
                    @endforeach
                </select>
                <p class="text-slate-400 mt-1">The next available pass in this building will be assigned automatically.</p>
            </div>
            {{-- Multi-building: check every building this pass should open --}}
            <div id="multiBuildingField" class="hidden">
                <label class="block mb-1">Authorized buildings * <span class="text-slate-400">(select 2 or more)</span></label>
                <div class="grid grid-cols-2 gap-2 border border-slate-200 rounded-lg p-3 max-h-40 overflow-y-auto">
                    @foreach ($buildings as $b)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="building_ids_multi" value="{{ $b->id }}" onchange="updateMultiCount()">
                            <span class="w-2 h-2 rounded-full" style="background:{{ $b->color_hex }}"></span>
                            {{ $b->name }}
                        </label>
                    @endforeach
                </div>
                <p class="text-slate-400 mt-1" id="multiCountHint">0 buildings selected</p>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeRegisterModal()" class="px-4 py-2 rounded-lg btn-govt-ghost">Cancel</button>
                <button type="submit" id="registerSubmitBtn" class="px-4 py-2 rounded-lg btn-govt-cta">Assign &amp; issue</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function setPassType(type) {
        document.getElementById('passTypeInput').value = type;

        const single = document.getElementById('singlePassField');
        const multi = document.getElementById('multiBuildingField');
        const passIdSelect = document.getElementById('buildingIdSelect');
        const btnSingle = document.getElementById('passTypeBtnSingle');
        const btnMulti = document.getElementById('passTypeBtnMulti');

        if (type === 'multi') {
            single.classList.add('hidden');
            multi.classList.remove('hidden');
            passIdSelect.removeAttribute('required');
            btnSingle.classList.remove('is-active');
            btnMulti.classList.add('is-active');
        } else {
            single.classList.remove('hidden');
            multi.classList.add('hidden');
            passIdSelect.setAttribute('required', 'required');
            btnSingle.classList.add('is-active');
            btnMulti.classList.remove('is-active');
        }

        updateMultiCount();
    }

    function updateMultiCount() {
        const checked = document.querySelectorAll('input[name="building_ids_multi"]:checked').length;
        document.getElementById('multiCountHint').textContent = checked + ' building' + (checked === 1 ? '' : 's') + ' selected';

        const submitBtn = document.getElementById('registerSubmitBtn');
        const type = document.getElementById('passTypeInput').value;
        submitBtn.disabled = (type === 'multi' && checked < 2);
        submitBtn.style.opacity = submitBtn.disabled ? '0.5' : '1';
    }

    // ---- Photo capture (new) ----
    let photoStream = null;

    async function startCamera() {
        const video = document.getElementById('photoVideo');
        try {
            photoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
            video.srcObject = photoStream;
            video.classList.remove('hidden');
            document.getElementById('startCameraBtn').classList.add('hidden');
            document.getElementById('captureBtn').classList.remove('hidden');
        } catch (err) {
            alert('Could not access camera: ' + err.message);
        }
    }

    function capturePhoto() {
        const video = document.getElementById('photoVideo');
        const canvas = document.getElementById('photoCanvas');
        const preview = document.getElementById('photoPreview');

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        document.getElementById('photoDataInput').value = dataUrl;

        preview.src = dataUrl;
        preview.classList.remove('hidden');
        video.classList.add('hidden');
        document.getElementById('captureBtn').classList.add('hidden');
        document.getElementById('retakeBtn').classList.remove('hidden');

        stopCameraStream();
    }

    function retakePhoto() {
        document.getElementById('photoPreview').classList.add('hidden');
        document.getElementById('retakeBtn').classList.add('hidden');
        document.getElementById('photoDataInput').value = '';
        startCamera();
    }

    function stopCameraStream() {
        if (photoStream) {
            photoStream.getTracks().forEach(track => track.stop());
            photoStream = null;
        }
    }

    function resetPhotoCapture() {
        stopCameraStream();
        document.getElementById('photoVideo').classList.add('hidden');
        document.getElementById('photoPreview').classList.add('hidden');
        document.getElementById('retakeBtn').classList.add('hidden');
        document.getElementById('captureBtn').classList.add('hidden');
        document.getElementById('startCameraBtn').classList.remove('hidden');
        document.getElementById('photoDataInput').value = '';
    }
    // ---- End photo capture ----

    function closeRegisterModal() {
        document.getElementById('registerModal').classList.add('hidden');
        document.getElementById('registerForm').reset();
        resetPhotoCapture();
        setPassType('single');
    }

    document.getElementById('registerForm').addEventListener('submit', function (e) {
        const type = document.getElementById('passTypeInput').value;
        if (type === 'multi') {
            const checked = document.querySelectorAll('input[name="building_ids_multi"]:checked');
            if (checked.length < 2) {
                e.preventDefault();
                alert('Select at least 2 buildings for a Multiple Access pass.');
                return;
            }
            checked.forEach(function (cb) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'building_ids[]';
                hidden.value = cb.value;
                this.appendChild(hidden);
            }, this);
        }
    });
</script>
@endsection

{{-- resources/views/scanner/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Scanner Terminal')

@section('content')
<div class="hero-govt">
    <div class="sunburst-red" aria-hidden="true"></div>

    <div class="hero-frame">
        <div class="hero-frame-rattan" aria-hidden="true"></div>

        <div class="hero-inner-panel flex items-center gap-5">
            <div class="hero-logo-badge hidden sm:flex">
                <img src="{{ asset('images/lsb-icon.png') }}" alt="LSB emblem">
            </div>
            <div>
                <p class="eyebrow">Perimeter Security Group &middot; Scanner Terminal</p>
                <h1>Visitor Access Scanner</h1>
                <p class="lead">Scan a visitor's pass to validate the authorized building through this scanner.</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    {{-- Yellow: live feed, tall left column --}}
    <div class="lg:col-span-6 card-govt card-vivid p-5 shadow-sm space-y-4" style="--ribbon-color: var(--brand-gold)">
        <div class="flex justify-between items-center">
            <div>
                <span class="tag-pill tag-pill-blue">Live feed</span>
                <h2 class="card-header-title mt-1"><i class="fa-solid fa-camera"></i> Live scanner feed</h2>
            </div>
            <button onclick="toggleCamera()" id="toggleCamBtn" class="text-xs btn-govt-primary px-3 py-1.5 rounded-lg">
                <i class="fa-solid fa-power-off"></i> Start webcam
            </button>
        </div>

        <div class="scanner-viewport-inset">
            <div class="relative scanner-feed overflow-hidden aspect-square flex items-center justify-center">
                <div id="reader" class="w-full h-full"></div>
                <div id="camPlaceholder" class="scanner-placeholder absolute inset-0 flex flex-col items-center justify-center p-6 text-center">
                    <i class="fa-solid fa-camera text-5xl mb-3"></i>
                    <p class="text-xs">Awaiting camera feed.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Right column: Red (station location) stacked over Blue (result) --}}
    <div class="lg:col-span-6 flex flex-col gap-6">

        <div class="card-govt card-vivid p-5 shadow-sm" style="--ribbon-color: var(--brand-red)">
            <label class="card-header-title flex items-center gap-2 mb-2">
                <i class="fa-solid fa-building-circle-check"></i> Personnel Station Location
            </label>
            <select id="scannerBuildingId" class="scanner-select w-full rounded-2xl px-4 py-3 font-bold">
                @foreach ($buildings as $b)
                    <option value="{{ $b->id }}">{{ $b->name }} Entrance</option>
                @endforeach
            </select>
        </div>

        <div id="resultCard" class="card-govt card-vivid p-5 shadow-sm flex-grow flex flex-col gap-4" style="--ribbon-color: var(--brand-blue)">
            <div id="resultIdle" class="py-12 flex flex-col items-center justify-center text-center space-y-3">
                <div class="w-20 h-20 bg-white text-slate-400 rounded-full flex items-center justify-center text-3xl">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <span class="tag-pill tag-pill-red">Scan result</span>
                <h3 class="text-lg font-bold text-white">Awaiting pass scan</h3>
            </div>

            <div id="resultActive" class="hidden flex flex-col gap-4">
                <div id="statusHeader" class="status-banner-card">
                    <div>
                        <div id="statusText" class="status-title"></div>
                        <div id="statusSubtitle" class="status-subtitle"></div>
                    </div>
                    <div id="scanTimestamp" class="status-timestamp"></div>
                </div>

                <div class="meta-grid-card">
                    <div><span class="meta-label">Visitor</span><div id="resVisitorName" class="meta-value"></div></div>
                    <div><span class="meta-label">Pass #</span><div id="resPassNum" class="meta-value font-mono"></div></div>
                    <div><span class="meta-label">Authorized Bldg.</span><div id="resPassBldg" class="meta-value"></div></div>
                    <div><span class="meta-label">Scanned At</span><div id="resScanLoc" class="meta-value"></div></div>
                </div>

                <div id="securityAdvisory" class="advisory-card"><span id="advisoryText"></span></div>
            </div>
        </div>

    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
let html5QrcodeScanner = null;
let isCameraActive = false;
let lastScannedToken = null;
let scanCooldownActive = false;

function playAudioFeedback(authorized) {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        if (authorized) {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1);
        } else {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(180, ctx.currentTime);
            osc.frequency.setValueAtTime(130, ctx.currentTime + 0.15);
        }
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
        osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.4);
    } catch(e) {}
}

async function processScanToken(token) {
    if (!token) return;
    const buildingId = document.getElementById('scannerBuildingId').value;

    const res = await fetch('{{ route('scanner.scan') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ token: token, scanned_building_id: buildingId })
    });
    const data = await res.json();
    displayScanResultUI(data);
}

function displayScanResultUI(data) {
    document.getElementById('resultIdle').classList.add('hidden');
    document.getElementById('resultActive').classList.remove('hidden');

    document.getElementById('scanTimestamp').innerText = data.timestamp;
    document.getElementById('resVisitorName').innerText = data.visitor_name;
    document.getElementById('resPassNum').innerText = data.pass_number;
    document.getElementById('resPassBldg').innerText = data.authorized_building;
    document.getElementById('resScanLoc').innerText = data.scanned_building;

    const header = document.getElementById('statusHeader');
    const advisory = document.getElementById('advisoryText');
    const advisoryBox = document.getElementById('securityAdvisory');

    if (data.result === 'AUTHORIZED') {
        header.className = 'status-banner-card is-authorized';
        document.getElementById('statusText').innerText = 'Access authorized';
        document.getElementById('statusSubtitle').innerText = 'Visitor authorized for this building';
        advisoryBox.className = 'advisory-card is-authorized';
        advisory.innerText = `Confirmed: ${data.visitor_name} holds a valid pass for ${data.scanned_building}.`;
    } else {
        header.className = 'status-banner-card is-denied';
        document.getElementById('statusText').innerText = 'Denied: ' + data.result.charAt(0) + data.result.slice(1).toLowerCase();
        document.getElementById('statusSubtitle').innerText = 'Security alert';
        advisoryBox.className = 'advisory-card is-denied';
        advisory.innerText = data.reason;
    }
    playAudioFeedback(data.result === 'AUTHORIZED');
}

function toggleCamera() {
    const btn = document.getElementById('toggleCamBtn');
    const placeholder = document.getElementById('camPlaceholder');
    if (!isCameraActive) {
        html5QrcodeScanner = new Html5Qrcode("reader");
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 220, height: 220 } },
            (decodedText) => {
                if (scanCooldownActive) return;
                if (decodedText === lastScannedToken) return;
                lastScannedToken = decodedText;
                scanCooldownActive = true;
                processScanToken(decodedText);
                setTimeout(() => {
                    scanCooldownActive = false;
                    lastScannedToken = null;
                }, 3000);
            },
            () => {}
        ).then(() => {
            isCameraActive = true;
            btn.innerHTML = '<i class="fa-solid fa-stop"></i> Stop camera';
            btn.className = "text-xs btn-govt-outline px-3 py-1.5 rounded-lg";
            btn.style.borderColor = 'var(--status-denied)';
            btn.style.color = 'var(--status-denied)';
            placeholder.classList.add('hidden');
        }).catch(err => alert("Camera error: " + err));
    } else {
        html5QrcodeScanner.stop().then(() => {
            html5QrcodeScanner.clear();
            isCameraActive = false;
            btn.innerHTML = '<i class="fa-solid fa-power-off"></i> Start webcam';
            btn.className = "text-xs btn-govt-primary px-3 py-1.5 rounded-lg";
            btn.style.borderColor = '';
            btn.style.color = '';
            placeholder.classList.remove('hidden');
        });
    }
}
</script>
@endsection
@extends('layouts.app')
@section('title', 'Scanner Terminal')

@section('content')
<div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap items-center justify-between gap-4">
    <div>
        <label class="text-xs uppercase font-bold text-slate-400 block mb-1">Guard Station Location</label>
        <select id="scannerBuildingId" class="font-bold text-slate-800 bg-slate-50 border border-slate-300 rounded-lg px-3 py-2">
            @foreach ($buildings as $b)
                <option value="{{ $b->id }}">{{ $b->name }} Entrance</option>
            @endforeach
        </select>
    </div>

    <div class="flex items-center gap-2 bg-amber-50 p-2.5 rounded-xl border border-amber-200">
        <span class="text-xs font-semibold text-amber-900"><i class="fa-solid fa-bolt"></i> Fast Test Scan:</span>
        <select id="quickScanSelect" class="text-xs bg-white border border-amber-300 rounded-md px-2 py-1 font-mono">
            <option value="">Select a Pass...</option>
            @foreach ($passes as $p)
                <option value="{{ $p->qr_token }}">[{{ $p->building->name }}] {{ $p->visitor_name ?: 'Unassigned' }} ({{ $p->pass_number }})</option>
            @endforeach
        </select>
        <button onclick="triggerQuickScan()" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1 rounded-md text-xs font-bold">Scan</button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-5 bg-white p-5 rounded-2xl shadow-sm border border-slate-200 space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-slate-800"><i class="fa-solid fa-camera text-slate-500"></i> Live Scanner Feed</h2>
            <button onclick="toggleCamera()" id="toggleCamBtn" class="text-xs bg-slate-800 text-white font-semibold px-3 py-1.5 rounded-lg">
                <i class="fa-solid fa-power-off"></i> Start Webcam
            </button>
        </div>
        <div class="relative bg-slate-950 rounded-xl overflow-hidden aspect-square border-2 border-slate-800 flex items-center justify-center">
            <div id="reader" class="w-full h-full"></div>
            <div id="camPlaceholder" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 p-6 text-center">
                <i class="fa-solid fa-qrcode text-5xl text-slate-600 mb-3"></i>
                <p class="text-xs">Click "Start Webcam" or use manual input below.</p>
            </div>
        </div>
        <form onsubmit="handleManualScanSubmit(event)" class="flex gap-2 pt-2 border-t border-slate-100">
            <input type="text" id="manualTokenInput" placeholder="Scan or paste QR token..." class="flex-grow px-3 py-2 text-sm border border-slate-300 rounded-lg font-mono">
            <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Validate</button>
        </form>
    </div>

    <div class="lg:col-span-7">
        <div id="resultCard" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 min-h-[360px] flex flex-col justify-between">
            <div id="resultIdle" class="py-12 flex flex-col items-center justify-center text-center space-y-3">
                <div class="w-20 h-20 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center text-3xl">
                    <i class="fa-solid fa-id-badge"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-700">Awaiting Pass Scan</h3>
            </div>

            <div id="resultActive" class="hidden space-y-5">
                <div id="statusHeader" class="p-4 rounded-xl text-white flex items-center justify-between shadow-md">
                    <div>
                        <div id="statusText" class="text-2xl font-black uppercase"></div>
                        <div id="statusSubtitle" class="text-xs text-white/90"></div>
                    </div>
                    <div id="scanTimestamp" class="text-sm font-bold font-mono"></div>
                </div>
                <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-sm">
                    <div><span class="text-xs font-semibold text-slate-400 uppercase">Visitor</span><div id="resVisitorName" class="font-bold"></div></div>
                    <div><span class="text-xs font-semibold text-slate-400 uppercase">Pass #</span><div id="resPassNum" class="font-bold font-mono"></div></div>
                    <div><span class="text-xs font-semibold text-slate-400 uppercase">Authorized Bldg</span><div id="resPassBldg" class="font-bold"></div></div>
                    <div><span class="text-xs font-semibold text-slate-400 uppercase">Scanned At</span><div id="resScanLoc" class="font-bold"></div></div>
                </div>
                <div id="securityAdvisory" class="p-3.5 rounded-xl border text-xs" id="advisoryText"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
let html5QrcodeScanner = null;
let isCameraActive = false;

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
        header.className = 'p-4 rounded-xl text-white flex items-center justify-between shadow-md bg-emerald-600';
        document.getElementById('statusText').innerText = 'ACCESS AUTHORIZED';
        document.getElementById('statusSubtitle').innerText = 'Visitor authorized for this building';
        advisoryBox.className = 'p-3.5 rounded-xl border text-xs bg-emerald-50 border-emerald-200 text-emerald-900';
        advisory.innerText = `Confirmed: ${data.visitor_name} holds a valid pass for ${data.scanned_building}.`;
    } else {
        header.className = 'p-4 rounded-xl text-white flex items-center justify-between shadow-md bg-red-600';
        document.getElementById('statusText').innerText = 'DENIED: ' + data.result;
        document.getElementById('statusSubtitle').innerText = 'Security alert';
        advisoryBox.className = 'p-3.5 rounded-xl border text-xs bg-red-50 border-red-200 text-red-900 font-semibold';
        advisory.innerText = data.reason;
    }
    playAudioFeedback(data.result === 'AUTHORIZED');
}

function triggerQuickScan() {
    const token = document.getElementById('quickScanSelect').value;
    if (token) processScanToken(token);
}

function handleManualScanSubmit(e) {
    e.preventDefault();
    const input = document.getElementById('manualTokenInput');
    if (input.value.trim()) { processScanToken(input.value.trim()); input.value = ''; }
}

function toggleCamera() {
    const btn = document.getElementById('toggleCamBtn');
    const placeholder = document.getElementById('camPlaceholder');
    if (!isCameraActive) {
        html5QrcodeScanner = new Html5Qrcode("reader");
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 220, height: 220 } },
            (decodedText) => processScanToken(decodedText),
            () => {}
        ).then(() => {
            isCameraActive = true;
            btn.innerHTML = '<i class="fa-solid fa-stop"></i> Stop Camera';
            btn.className = "text-xs bg-red-600 text-white font-semibold px-3 py-1.5 rounded-lg";
            placeholder.classList.add('hidden');
        }).catch(err => alert("Camera error: " + err));
    } else {
        html5QrcodeScanner.stop().then(() => {
            html5QrcodeScanner.clear();
            isCameraActive = false;
            btn.innerHTML = '<i class="fa-solid fa-power-off"></i> Start Webcam';
            btn.className = "text-xs bg-slate-800 text-white font-semibold px-3 py-1.5 rounded-lg";
            placeholder.classList.remove('hidden');
        });
    }
}
</script>
@endsection
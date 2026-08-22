@extends('layouts.app')
@section('title', 'Pass Preview')

@section('content')
<div class="flex justify-center">
    <div class="space-y-4">
        <div id="printablePassArea" class="relative w-[300px] h-[500px] rounded-xl shadow-xl overflow-hidden border-2 border-slate-300"
             style="background-image:url('{{ asset($pass->building->template_image) }}'); background-size:cover; background-position:center;">

            <!-- QR overlay - adjust top/left % if misaligned with your template's dashed box -->
            <div class="absolute left-1/2 -translate-x-1/2 bg-white p-1.5 rounded-lg" style="top:44%;">
                <div id="qrCodeContainer"></div>
            </div>

            <!-- Pass number overlay -->
            <div class="absolute left-1/2 -translate-x-1/2 text-white font-black font-mono drop-shadow-md" style="top:72%; font-size:2.5rem;">
                {{ $pass->pass_number }}
            </div>
        </div>

        <div class="flex gap-2">
            <button onclick="window.print()" class="flex-1 bg-slate-900 text-white font-bold py-2 rounded-xl text-xs"><i class="fa-solid fa-print"></i> Print</button>
            <a href="{{ route('passes.index') }}" class="flex-1 bg-slate-200 text-slate-800 font-bold py-2 rounded-xl text-xs text-center">Back</a>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("qrCodeContainer"), {
    text: "{{ $pass->qr_token }}",
    width: 130, height: 130,
    colorDark: "{{ $pass->building->qr_color_hex }}",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});
</script>

<style>
@media print {
    body * { visibility: hidden; }
    #printablePassArea, #printablePassArea * { visibility: visible; }
    #printablePassArea { position: fixed; left: 0; top: 0; }
}
</style>
@endsection
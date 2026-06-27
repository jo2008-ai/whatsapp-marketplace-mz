@extends('layouts.painel')
@section('title', 'WhatsApp')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- Instâncias existentes -->
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Instâncias WhatsApp</h3>

        @forelse($instancias as $inst)
        <div class="border rounded-lg p-4 mb-3 {{ $inst->estado === 'conectada' ? 'border-green-200 bg-green-50' : ($inst->estado === 'aguarda_qr' ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200') }}">
            <div class="flex justify-between items-center">
                <div>
                    <div class="font-medium text-gray-800">{{ $inst->nome_instancia }}</div>
                    <div class="text-xs text-gray-500">{{ $inst->numero_whatsapp ?? 'Sem número' }}</div>
                    @if($inst->waha_url)
                        <div class="text-xs text-gray-400 truncate" title="{{ $inst->waha_url }}">WAHA: {{ $inst->waha_url }}</div>
                    @endif
                </div>
                @if($inst->estado === 'conectada')
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">🟢 Conectada</span>
                @elseif($inst->estado === 'aguarda_qr')
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">🟡 Aguarda QR</span>
                @else
                    <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">🔴 Desconectada</span>
                @endif
            </div>
            @if($inst->estado === 'aguarda_qr')
                <button onclick="iniciarQR({{ $inst->id }})" class="mt-3 w-full py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    Obter QR Code
                </button>
            @endif
        </div>
        @empty
        <p class="text-gray-400 text-sm">Nenhuma instância configurada.</p>
        @endforelse

        <form method="POST" action="/painel/whatsapp/conectar" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Servidor WAHA</label>
                <select name="waha_url" class="w-full px-3 py-2 border rounded-lg text-sm">
                    @foreach($wahaUrls as $id => $url)
                        <option value="{{ $url }}" {{ $id == $tenantId ? 'selected' : '' }}>
                            WAHA #{{ $id }} — {{ $url }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 hover:border-blue-400 hover:text-blue-600 text-sm">
                + Adicionar novo número
            </button>
        </form>
    </div>

    <!-- QR Code -->
    <div class="bg-white rounded-xl shadow p-5" id="qr-section" style="display: none;">
        <h3 class="font-semibold text-gray-800 mb-4">Escanear QR Code</h3>
        <div class="text-center">
            <div id="qr-loading" class="py-8">
                <div class="animate-spin inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full"></div>
                <p class="mt-2 text-sm text-gray-500">A obter QR code...</p>
            </div>
            <div id="qr-image" style="display: none;">
                <img id="qr-img" src="" alt="QR Code" class="mx-auto max-w-xs rounded-lg">
                <p class="mt-3 text-sm text-gray-500">Abre o WhatsApp no teu telefone → Dispositivos ligados → Ligar dispositivo</p>
            </div>
            <div id="qr-conectado" style="display: none;">
                <div class="text-4xl mb-2">✅</div>
                <p class="text-green-600 font-semibold">WhatsApp conectado!</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const tenantId = {{ $tenantId ?? 1 }};

    let pollingInterval = null;
    let instanciaAtual = null;

    function iniciarQR(instanciaId) {
        instanciaAtual = instanciaId;
        document.getElementById('qr-section').style.display = 'block';
        document.getElementById('qr-loading').style.display = 'block';
        document.getElementById('qr-image').style.display = 'none';
        document.getElementById('qr-conectado').style.display = 'none';

        if (pollingInterval) clearInterval(pollingInterval);
        buscarQR();
        pollingInterval = setInterval(buscarQR, 3000);
    }

    async function buscarQR() {
        try {
            const resp = await fetch(`/painel/whatsapp/qr?instancia=${instanciaAtual}`);
            const data = await resp.json();

            if (data.erro) {
                document.getElementById('qr-loading').textContent = data.erro;
                clearInterval(pollingInterval);
                return;
            }

            if (data.estado === 'conectada') {
                clearInterval(pollingInterval);
                document.getElementById('qr-loading').style.display = 'none';
                document.getElementById('qr-image').style.display = 'none';
                document.getElementById('qr-conectado').style.display = 'block';
                setTimeout(() => location.reload(), 2000);
                return;
            }

            if (data.qr) {
                document.getElementById('qr-loading').style.display = 'none';
                document.getElementById('qr-image').style.display = 'block';

                const qrData = data.qr;
                if (typeof qrData === 'string') {
                    document.getElementById('qr-img').src = qrData;
                } else if (qrData.base64) {
                    document.getElementById('qr-img').src = 'data:image/png;base64,' + qrData.base64;
                } else if (qrData.qrcode) {
                    document.getElementById('qr-img').src = qrData.qrcode;
                }
            }
        } catch (e) {
            console.error('Erro ao buscar QR:', e);
        }
    }
</script>
@endpush

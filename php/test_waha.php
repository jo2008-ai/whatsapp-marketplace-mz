<?php

/**
 * Script de teste para verificar integração com WAHA
 * Uso: php test_waha.php [tenant_id] [numero_destino]
 * Exemplo: php test_waha.php 1 258841234567
 */

require __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$wahaUrl = $_ENV['WAHA_URL_1'] ?? 'http://localhost:3000';
$apiKey = $_ENV['WAHA_API_KEY'] ?? '';

$tenantId = $argv[1] ?? null;
$numeroDestino = $argv[2] ?? null;

echo "=== TESTE DE INTEGRAÇÃO WAHA ===\n\n";
echo "URL: {$wahaUrl}\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo str_repeat("-", 50) . "\n\n";

// 1. Health Check - Verificar se WAHA está acessível
echo "[1] Health Check - Verificando se WAHA está acessível...\n";
$ch = curl_init("{$wahaUrl}/api/sessions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Api-Key: {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "    ✅ WAHA está acessível! (HTTP {$httpCode})\n\n";
    
    $sessions = json_decode($response, true);
    echo "    Sessões ativas: " . count($sessions) . "\n";
    if (count($sessions) > 0) {
        foreach ($sessions as $session) {
            $name = $session['name'] ?? 'N/A';
            $status = $session['status'] ?? 'N/A';
            $number = $session['phone'] ?? 'N/A';
            echo "    - {$name}: {$status} (Tel: {$number})\n";
        }
    }
    echo "\n";
} else {
    echo "    ❌ WAHA não está acessível! (HTTP {$httpCode})\n";
    echo "    Resposta: {$response}\n\n";
    exit(1);
}

// 2. Verificar instância específica do tenant
if ($tenantId) {
    echo "[2] Verificando instância do tenant {$tenantId}...\n";
    $sessionName = "loja-{$tenantId}";
    
    $ch = curl_init("{$wahaUrl}/api/sessions/{$sessionName}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Api-Key: {$apiKey}",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $session = json_decode($response, true);
        echo "    ✅ Sessão '{$sessionName}' encontrada!\n";
        echo "    Status: " . ($session['status'] ?? 'N/A') . "\n";
        echo "    Telefone: " . ($session['phone'] ?? 'N/A') . "\n";
        echo "    Engine: " . ($session['engine'] ?? 'N/A') . "\n\n";
        
        // 3. Testar envio de mensagem (se número fornecido)
        if ($numeroDestino) {
            echo "[3] Testando envio de mensagem para {$numeroDestino}...\n";
            $mensagem = "Teste de integração Marketplace MZ - " . date('H:i:s');
            
            $payload = json_encode([
                "chatId" => "{$numeroDestino}@c.us",
                "text" => $mensagem
            ]);
            
            $ch = curl_init("{$wahaUrl}/api/sendText");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-Api-Key: {$apiKey}",
                "Content-Type: application/json"
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 || $httpCode === 201) {
                echo "    ✅ Mensagem enviada com sucesso!\n";
                echo "    Mensagem: {$mensagem}\n";
                $result = json_decode($response, true);
                echo "    Message ID: " . ($result['key']['id'] ?? 'N/A') . "\n\n";
            } else {
                echo "    ❌ Erro ao enviar mensagem! (HTTP {$httpCode})\n";
                echo "    Resposta: {$response}\n\n";
            }
        } else {
            echo "[3] Para testar envio, execute: php test_waha.php {$tenantId} [numero]\n\n";
        }
    } elseif ($httpCode === 404) {
        echo "    ⚠️  Sessão '{$sessionName}' não encontrada.\n";
        echo "    A instância pode não ter sido criada ainda.\n\n";
    } else {
        echo "    ❌ Erro ao verificar instância! (HTTP {$httpCode})\n";
        echo "    Resposta: {$response}\n\n";
    }
}

echo "=== FIM DO TESTE ===\n";

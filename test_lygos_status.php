<?php
/**
 * Script de test pour vérifier le statut d'un paiement Lygos
 *
 * Usage: php test_lygos_status.php <order_id>
 * Exemple: php test_lygos_status.php xyz12345
 */

require __DIR__.'/vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (!isset($argv[1])) {
    echo "❌ Usage: php test_lygos_status.php <order_id>\n";
    echo "Exemple: php test_lygos_status.php xyz12345\n";
    exit(1);
}

$orderId = $argv[1];
$apiKey = $_ENV['LIGOSAPP_API_KEY'] ?? '';
$baseUrl = $_ENV['LIGOSAPP_BASE_URL'] ?? 'https://api.lygosapp.com/v1';

if (empty($apiKey)) {
    echo "❌ LIGOSAPP_API_KEY non définie dans .env\n";
    exit(1);
}

echo "🔍 Test de vérification du statut Lygos\n";
echo "=====================================\n";
echo "Order ID: $orderId\n";
echo "API URL: $baseUrl/gateway/payin/$orderId\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "\n";

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "$baseUrl/gateway/payin/$orderId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "api-key: $apiKey"
    ],
]);

echo "📤 Envoi de la requête...\n\n";

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "❌ Erreur cURL: $err\n";
    exit(1);
}

echo "📥 Réponse reçue (HTTP $httpCode):\n";
echo "=====================================\n";
echo $response . "\n";
echo "=====================================\n\n";

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Erreur de décodage JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "📊 Analyse de la réponse:\n";
echo "=====================================\n";
echo "Statut retourné: " . ($data['status'] ?? 'NON DÉFINI') . "\n";
echo "Order ID: " . ($data['order_id'] ?? 'NON DÉFINI') . "\n";
echo "\n";

if (isset($data['status'])) {
    $status = strtolower($data['status']);

    echo "🎯 Interprétation du statut:\n";
    echo "Statut (lowercase): $status\n";

    echo "📚 Selon le SDK officiel Lygos (github.com/Warano02/lygos):\n";
    echo "   - 'success' = Paiement réussi ✅\n";
    echo "   - 'pending' = Paiement en attente ⏳\n";
    echo "   - 'failed' = Paiement échoué ❌\n\n";

    if ($status === 'success') {
        echo "✅ PAIEMENT RÉUSSI - L'identité devrait être révélée\n";
    } elseif ($status === 'failed') {
        echo "❌ PAIEMENT ÉCHOUÉ - L'identité NE doit PAS être révélée\n";
    } elseif ($status === 'pending') {
        echo "⏳ PAIEMENT EN COURS - Attendre\n";
    } else {
        echo "⚠️  STATUT INCONNU: '$status'\n";
        echo "   Ce statut n'est PAS dans la documentation officielle!\n";
        echo "   Statuts valides selon Lygos: success, pending, failed\n";
        echo "\n";
        echo "   ⚠️  SÉCURITÉ: N'accepter QUE 'success' pour révéler l'identité!\n";
    }
}

echo "\n";
echo "📋 Données complètes:\n";
print_r($data);

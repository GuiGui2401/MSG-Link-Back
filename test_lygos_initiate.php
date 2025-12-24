<?php
/**
 * Script pour voir la structure complète d'une réponse d'initialisation Lygos
 */

require __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['LIGOSAPP_API_KEY'] ?? '';
$baseUrl = $_ENV['LIGOSAPP_BASE_URL'] ?? 'https://api.lygosapp.com/v1';

if (empty($apiKey)) {
    echo "❌ LIGOSAPP_API_KEY non définie dans .env\n";
    exit(1);
}

echo "🔍 Recherche d'une transaction récente dans les logs...\n\n";

// Lire le fichier de logs pour trouver une réponse d'initialisation
$logFile = __DIR__ . '/storage/logs/laravel.log';
$logs = file_get_contents($logFile);

// Chercher les réponses d'initialisation
preg_match_all('/✅ \[LYGOS\] Paiement initialisé avec succès.*?\{(.+?)\}/', $logs, $matches);

if (empty($matches[1])) {
    echo "❌ Aucune initialisation trouvée dans les logs\n";
    echo "\n💡 Faites une initialisation de paiement d'abord\n";
    exit(1);
}

echo "📋 Dernière réponse d'initialisation trouvée:\n";
echo "=====================================\n";
$lastMatch = end($matches[0]);
echo $lastMatch . "\n";
echo "=====================================\n\n";

echo "💡 Pour voir la structure complète de l'API Lygos, consultez:\n";
echo "   https://docs.lygosapp.com/\n\n";

echo "📌 Points importants à vérifier:\n";
echo "   1. Y a-t-il un champ 'payment_status' dans la réponse?\n";
echo "   2. Y a-t-il un champ 'result' ou 'result_code'?\n";
echo "   3. Y a-t-il un champ 'amount_received'?\n";
echo "   4. Comment différencier un paiement réussi d'un paiement échoué?\n";

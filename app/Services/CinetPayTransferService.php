<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CinetPayTransferService
{
    protected $apiKey;
    protected $transferPassword;
    protected $maxRetries = 3;
    protected $retryDelays = [10, 30, 60]; // Délais entre tentatives en secondes

    public function __construct()
    {
        $this->apiKey = Setting::get('cinetpay_api_key', config('cinetpay.api_key'));
        $this->transferPassword = Setting::get('cinetpay_transfer_password', config('cinetpay.transfer_password'));
    }

    /**
     * Exécuter un transfert CinetPay de manière synchrone avec système de retry
     */
    public function executeTransfer(Transaction $transaction, User $user, $amount, $phoneNumber, $operator)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                Log::info("=== TENTATIVE {$attempt}/{$this->maxRetries} TRANSFERT CINETPAY ===", [
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'attempt' => $attempt
                ]);

                // 1. Authentification
                $authResponse = $this->authenticate();
                if (!$authResponse['success']) {
                    throw new Exception('Échec de l\'authentification CinetPay: ' . ($authResponse['error'] ?? 'Erreur inconnue'));
                }

                $token = $authResponse['token'];

                // 2. Créer/Vérifier le contact
                $contactResponse = $this->createContact($token, $phoneNumber, $user);
                if (!$contactResponse['success']) {
                    throw new Exception('Échec de la création du contact: ' . ($contactResponse['error'] ?? 'Erreur inconnue'));
                }

                // 3. Initier le transfert
                $timeout = 30 + ($attempt * 20); // Timeout progressif: 50s, 70s, 90s
                $transferResponse = $this->initiateTransfer($token, $phoneNumber, $amount, $operator, $timeout);

                if ($transferResponse['success']) {
                    // Succès - Mise à jour de la transaction
                    $meta = $transaction->meta ?? [];
                    $meta['cinetpay_transfer_id'] = $transferResponse['transfer_id'];
                    $meta['transfer_status'] = 'initiated';
                    $meta['processed_at'] = now()->toISOString();
                    $meta['attempts'] = $attempt;

                    $transaction->update([
                        'status' => 'completed',
                        'meta' => $meta,
                        'completed_at' => now()
                    ]);

                    Log::info('✓ Transfert CinetPay initié avec succès', [
                        'transaction_id' => $transaction->id,
                        'transfer_id' => $transferResponse['transfer_id'],
                        'attempts' => $attempt
                    ]);

                    return [
                        'success' => true,
                        'transfer_id' => $transferResponse['transfer_id'],
                        'attempts' => $attempt
                    ];

                } else {
                    throw new Exception($transferResponse['message'] ?? 'Échec du transfert');
                }

            } catch (Exception $e) {
                $lastException = $e;

                Log::error("Échec tentative {$attempt}/{$this->maxRetries}", [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt
                ]);

                // Si ce n'est pas la dernière tentative, attendre avant de réessayer
                if ($attempt < $this->maxRetries) {
                    $delay = $this->retryDelays[$attempt - 1] ?? 60;
                    Log::info("Attente de {$delay} secondes avant nouvelle tentative...");
                    sleep($delay);
                }
            }
        }

        // Échec définitif après toutes les tentatives
        return $this->handleFailure($transaction, $user, $lastException);
    }

    /**
     * Authentification CinetPay
     */
    private function authenticate()
    {
        try {
            $response = Http::timeout(30)->asForm()->post('https://client.cinetpay.com/v1/auth/login', [
                'apikey' => $this->apiKey,
                'password' => $this->transferPassword
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['code']) && $data['code'] == 0 && isset($data['data']['token'])) {
                    return [
                        'success' => true,
                        'token' => $data['data']['token']
                    ];
                }
            }

            return ['success' => false, 'error' => 'Authentification échouée'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Créer ou récupérer un contact
     */
    private function createContact($token, $phoneNumber, $user)
    {
        try {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

            if (strlen($cleanPhone) == 9 && !str_starts_with($cleanPhone, '237')) {
                $prefix = '237';
                $phone = $cleanPhone;
            } elseif (str_starts_with($cleanPhone, '237')) {
                $prefix = '237';
                $phone = substr($cleanPhone, 3);
            } else {
                $prefix = '237';
                $phone = $cleanPhone;
            }

            $contactData = [
                [
                    'prefix' => $prefix,
                    'phone' => $phone,
                    'name' => $user->name ?? 'User',
                    'surname' => $user->name ?? 'Weylo',
                    'email' => $user->email
                ]
            ];

            $response = Http::timeout(30)->asForm()->post("https://client.cinetpay.com/v1/transfer/contact?token={$token}&lang=fr", [
                'data' => json_encode($contactData)
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['code']) && $data['code'] == 0) {
                    $contactInfo = $data['data'][0][0] ?? $data['data'][0] ?? null;

                    if ($contactInfo && isset($contactInfo['status'])) {
                        if ($contactInfo['status'] === 'success' || $contactInfo['status'] === 'ERROR_PHONE_ALREADY_MY_CONTACT') {
                            return ['success' => true];
                        }
                    }
                }
            }

            return ['success' => false, 'error' => 'Échec création contact'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Exécuter le transfert
     */
    private function initiateTransfer($token, $phoneNumber, $amount, $operator, $timeout = 30)
    {
        try {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

            if (strlen($cleanPhone) == 9 && !str_starts_with($cleanPhone, '237')) {
                $prefix = '237';
                $phone = $cleanPhone;
            } elseif (str_starts_with($cleanPhone, '237')) {
                $prefix = '237';
                $phone = substr($cleanPhone, 3);
            } else {
                $prefix = '237';
                $phone = $cleanPhone;
            }

            $transferData = [
                [
                    'prefix' => $prefix,
                    'phone' => $phone,
                    'amount' => (int) $amount,
                    'client_transaction_id' => 'TRANSFER_' . time() . '_' . random_int(1000, 9999),
                    'notify_url' => env('CINETPAY_NOTIFY_URL', url('/api/v1/cinetpay/notify')) . '/transfer'
                ]
            ];

            // NE PAS AJOUTER payment_method pour le Cameroun (prefix 237)
            // CinetPay détecte automatiquement l'opérateur à partir du numéro de téléphone
            // Ajouter payment_method seulement pour CI (WAVECI) et SN (WAVESN) si spécifié
            if ($operator && in_array($operator, ['WAVECI', 'WAVESN'])) {
                $transferData[0]['payment_method'] = $operator;
            }
            // Pour MTN, MOOV, ORANGE au Cameroun : ne rien ajouter (auto-détection)

            Log::info("Envoi transfert avec timeout de {$timeout}s", [
                'phone' => $prefix . $phone,
                'amount' => $amount,
                'operator' => $operator
            ]);

            $response = Http::timeout($timeout)->asForm()->post("https://client.cinetpay.com/v1/transfer/money/send/contact?token={$token}&lang=fr", [
                'data' => json_encode($transferData)
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['code']) && $data['code'] == 0) {
                    $transferInfo = $data['data'][0][0] ?? $data['data'][0] ?? null;

                    if ($transferInfo && isset($transferInfo['status']) && $transferInfo['status'] === 'success') {
                        return [
                            'success' => true,
                            'transfer_id' => $transferInfo['transaction_id'],
                            'client_transaction_id' => $transferInfo['client_transaction_id']
                        ];
                    }
                }
            }

            Log::error('CinetPay raw response', ['response' => $response->body()]);
            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Échec du transfert'
            ];

        } catch (Exception $e) {
            // Si c'est un timeout, on peut réessayer
            if (str_contains($e->getMessage(), 'cURL error 28')) {
                Log::warning('Timeout CinetPay détecté', ['timeout' => $timeout]);
            }

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Gérer l'échec définitif
     */
    private function handleFailure($transaction, $user, $exception)
    {
        // Marquer la transaction comme échouée
        $transaction->update(['status' => 'failed']);

        // Rembourser l'utilisateur
        $user->increment('wallet_balance', abs($transaction->amount));

        Log::error('Échec définitif du transfert CinetPay - Utilisateur remboursé', [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'amount' => abs($transaction->amount),
            'wallet_balance_after_refund' => $user->fresh()->wallet_balance,
            'error' => $exception ? $exception->getMessage() : 'Erreur inconnue'
        ]);

        return [
            'success' => false,
            'message' => 'Le transfert a échoué après plusieurs tentatives. Votre solde a été remboursé.',
            'refunded' => true
        ];
    }
}

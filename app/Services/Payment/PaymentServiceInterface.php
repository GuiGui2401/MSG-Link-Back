<?php

namespace App\Services\Payment;

use App\Models\User;

interface PaymentServiceInterface
{
    /**
     * Initier un paiement
     *
     * @param array $data [
     *   'amount' => int,
     *   'currency' => string,
     *   'description' => string,
     *   'reference' => string,
     *   'user' => User,
     *   'metadata' => array,
     * ]
     * @return array [
     *   'reference' => string,
     *   'payment_url' => string,
     *   'provider' => string,
     * ]
     */
    public function initiatePayment(array $data): array;

    /**
     * Vérifier le statut d'un paiement
     *
     * @param string $reference
     * @return array [
     *   'status' => string (pending, completed, failed),
     *   'provider_reference' => string,
     *   'amount' => int,
     *   'metadata' => array,
     * ]
     */
    public function checkPaymentStatus(string $reference): array;

    /**
     * Traiter le webhook de paiement
     *
     * @param array $payload
     * @return array [
     *   'success' => bool,
     *   'reference' => string,
     *   'status' => string,
     * ]
     */
    public function handleWebhook(array $payload): array;

    /**
     * Effectuer un transfert (pour les retraits)
     *
     * @param array $data [
     *   'amount' => int,
     *   'phone' => string,
     *   'provider' => string (mtn, orange),
     *   'reference' => string,
     * ]
     * @return array
     */
    public function initiateTransfer(array $data): array;
}

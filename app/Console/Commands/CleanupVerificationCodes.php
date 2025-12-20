<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\VerificationCode;
use Illuminate\Console\Command;

class CleanupVerificationCodes extends Command
{
    protected $signature = 'verification:cleanup';
    
    protected $description = 'Supprimer les codes de vérification expirés';

    public function handle(): int
    {
        $expiryMinutes = Setting::get('security_verification_code_expiry', 15);

        $deleted = VerificationCode::where('created_at', '<', now()->subMinutes($expiryMinutes))
            ->orWhere('used_at', '!=', null)
            ->delete();

        $this->info("Supprimé {$deleted} codes de vérification expirés.");

        return self::SUCCESS;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\GroupMessage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mettre à jour tous les messages système pour afficher "Anonyme"
        // IMPORTANT: Les messages sont chiffrés, on doit utiliser Eloquent

        $systemMessages = GroupMessage::where('type', GroupMessage::TYPE_SYSTEM)->get();

        foreach ($systemMessages as $message) {
            $content = $message->content;
            $newContent = null;

            // "Groupe créé par [nom]" -> "Groupe créé par Anonyme"
            if (preg_match('/^Groupe créé par (?!Anonyme$)/', $content)) {
                $newContent = 'Groupe créé par Anonyme';
            }
            // "[nom] a rejoint le groupe" -> "Anonyme a rejoint le groupe"
            elseif (preg_match('/^(.+) a rejoint le groupe$/', $content, $matches) && $matches[1] !== 'Anonyme') {
                $newContent = 'Anonyme a rejoint le groupe';
            }
            // "[nom] a quitté le groupe" -> "Anonyme a quitté le groupe"
            elseif (preg_match('/^(.+) a quitté le groupe$/', $content, $matches) && $matches[1] !== 'Anonyme') {
                $newContent = 'Anonyme a quitté le groupe';
            }
            // "[nom] a été retiré du groupe" -> "Anonyme a été retiré du groupe"
            elseif (preg_match('/^(.+) a été retiré du groupe$/', $content, $matches) && $matches[1] !== 'Anonyme') {
                $newContent = 'Anonyme a été retiré du groupe';
            }

            if ($newContent) {
                $message->content = $newContent;
                $message->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // On ne peut pas inverser cette migration car on ne connait plus les vrais noms
        // Les messages système resteront anonymes
    }
};

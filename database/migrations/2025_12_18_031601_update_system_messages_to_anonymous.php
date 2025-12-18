<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\GroupMessage;
use App\Models\GroupMember;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Récupérer tous les messages système
        $systemMessages = GroupMessage::where('type', GroupMessage::TYPE_SYSTEM)->get();

        foreach ($systemMessages as $message) {
            $content = $message->attributes['content'] ?? $message->content;

            // Récupérer le membre du groupe pour obtenir son nom anonyme
            $member = GroupMember::where('group_id', $message->group_id)
                ->where('user_id', $message->sender_id)
                ->first();

            if (!$member) {
                // Si le membre n'existe plus, supprimer le message
                $message->delete();
                continue;
            }

            $anonymousName = $member->anonymous_name;

            // Patterns à rechercher et remplacer
            $patterns = [
                // "Groupe créé par username" -> "Groupe créé par Panda56"
                '/Groupe créé par [^\s]+/' => "Groupe créé par {$anonymousName}",

                // "username a rejoint le groupe" -> "Panda56 a rejoint le groupe"
                '/^[^\s]+ a rejoint le groupe/' => "{$anonymousName} a rejoint le groupe",

                // "username a quitté le groupe" -> "Panda56 a quitté le groupe"
                '/^[^\s]+ a quitté le groupe/' => "{$anonymousName} a quitté le groupe",

                // "username a été retiré du groupe" -> "Panda56 a été retiré du groupe"
                '/^[^\s]+ a été retiré du groupe/' => "{$anonymousName} a été retiré du groupe",
            ];

            $newContent = $content;
            foreach ($patterns as $pattern => $replacement) {
                $newContent = preg_replace($pattern, $replacement, $newContent);
            }

            // Mettre à jour le message si le contenu a changé
            if ($newContent !== $content) {
                DB::table('group_messages')
                    ->where('id', $message->id)
                    ->update(['content' => $newContent]);
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

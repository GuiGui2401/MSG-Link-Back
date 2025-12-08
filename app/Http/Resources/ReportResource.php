<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Rapporteur
            'reporter' => new UserPublicResource($this->whenLoaded('reporter')),
            
            // Contenu signalé
            'reportable_type' => class_basename($this->reportable_type),
            'reportable_id' => $this->reportable_id,
            'reportable' => $this->when($this->relationLoaded('reportable'), function () {
                return $this->formatReportable();
            }),
            
            'reason' => $this->reason,
            'reason_label' => $this->reason_label,
            'description' => $this->description,
            
            'status' => $this->status,
            'status_label' => $this->status_label,
            'is_pending' => $this->is_pending,
            
            // Traitement
            'reviewer' => $this->when(
                $this->relationLoaded('reviewer') && $this->reviewer,
                fn() => new UserPublicResource($this->reviewer)
            ),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'action_taken' => $this->action_taken,
            
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
    
    private function formatReportable(): ?array
    {
        if (!$this->reportable) {
            return null;
        }
        
        $reportable = $this->reportable;
        $type = class_basename($this->reportable_type);
        
        return match ($type) {
            'User' => [
                'type' => 'user',
                'username' => $reportable->username,
                'full_name' => $reportable->full_name,
            ],
            'AnonymousMessage' => [
                'type' => 'message',
                'content_preview' => \Illuminate\Support\Str::limit($reportable->content, 100),
                'sender_id' => $reportable->sender_id,
            ],
            'Confession' => [
                'type' => 'confession',
                'content_preview' => \Illuminate\Support\Str::limit($reportable->content, 100),
                'confession_type' => $reportable->type,
            ],
            'ChatMessage' => [
                'type' => 'chat_message',
                'content_preview' => \Illuminate\Support\Str::limit($reportable->content, 100),
                'sender_id' => $reportable->sender_id,
            ],
            default => [
                'type' => strtolower($type),
                'id' => $reportable->id,
            ],
        };
    }
}

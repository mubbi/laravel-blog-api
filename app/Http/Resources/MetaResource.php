<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MetaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var array<int, array<string, mixed>> $links */
        $links = $this['links'] ?? [];

        return [
            'current_page' => $this['current_page'] ?? null,
            'from' => $this['from'] ?? null,
            'last_page' => $this['last_page'] ?? null,
            'links' => collect($links)->map(function (array $link): array {
                return [
                    'url' => $link['url'] ?? null,
                    'label' => $link['label'] ?? null,
                    'active' => $link['active'] ?? false,
                ];
            })->values(),
            'path' => $this['path'] ?? null,
            'per_page' => $this['per_page'] ?? null,
            'to' => $this['to'] ?? null,
            'total' => $this['total'] ?? null,
        ];
    }
}

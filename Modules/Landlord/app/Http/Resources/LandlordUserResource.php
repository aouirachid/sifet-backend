<?php

declare(strict_types=1);

namespace Modules\Landlord\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Landlord\Models\LandlordUser;

/**
 * @mixin LandlordUser
 */
class LandlordUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
        ];
    }
}

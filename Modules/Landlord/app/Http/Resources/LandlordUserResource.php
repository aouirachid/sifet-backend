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
        /** @var LandlordUser $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
        ];
    }
}

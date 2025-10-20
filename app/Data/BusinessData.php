<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Business;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class BusinessData extends Data
{
    public function __construct(
        public ?int $id = null,
        #[Required]
        #[StringType]
        #[Max(255)]
        public ?string $name = null,
        public ?int $owner_id = null,
    ) {}

    /**
     * Create from existing business model.
     */
    public static function fromModel(Business $business): self
    {
        return new self(
            id: $business->id,
            name: $business->name,
            owner_id: $business->owner_id,
        );
    }
}

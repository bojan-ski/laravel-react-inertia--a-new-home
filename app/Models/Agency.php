<?php

namespace App\Models;

use App\Enums\AgencyStatus;
use App\Enums\EuropeanCountry;
use App\Enums\UserStatus;
use BackedEnum;
use Database\Factories\AgencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'legal_name',
    'vat_id',
    'registration_number',
    'registration_country',
    'broker_register_number',
    'description',
    'phone',
    'email',
    'website',
    'address',
])]
class Agency extends Model
{
    /** @use HasFactory<AgencyFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => AgencyStatus::PendingVerification->value,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public const PROFILE_FIELDS = [
        'name',
        'legal_name',
        'vat_id',
        'registration_number',
        'registration_country',
        'broker_register_number',
        'email',
        'phone',
        'website',
        'address',
        'description',
    ];

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function pendingChange(): HasOne
    {
        return $this->hasOne(AgencyProfileChange::class);
    }

    public function profileValue(string $field): ?string
    {
        $value = $this->getAttribute($field);

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value === null ? null : (string) $value;
    }

    #[Scope]
    protected function publiclyVisible(Builder $query): void
    {
        $query->where('status', AgencyStatus::Approved->value)
            ->whereNotNull('approved_at')
            ->where('approved_at', '<=', now());
    }

    #[Scope]
    protected function actuallyPubliclyVisible(Builder $query): void
    {
        $query->publiclyVisible()
            ->whereDoesntHave('user', function (Builder $query) {
                $query->where('status', UserStatus::Suspended->value);
            });
    }

    #[Scope]
    protected function byStatus(Builder $query, AgencyStatus $status): void
    {
        $query->where('status', $status->value);
    }

    public function isPendingVerification(): bool
    {
        return $this->status === AgencyStatus::PendingVerification;
    }

    public function isApproved(): bool
    {
        return $this->status === AgencyStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === AgencyStatus::Rejected;
    }

    public function isHidden(): bool
    {
        return $this->status === AgencyStatus::Hidden;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isApproved()
            && $this->approved_at !== null
            && $this->approved_at->lessThanOrEqualTo(now());
    }

    public function shouldBeHiddenDueToSuspension(): bool
    {
        return (bool) $this->user?->isSuspended();
    }

    public function isActuallyPubliclyVisible(): bool
    {
        return $this->isPubliclyVisible() && ! $this->shouldBeHiddenDueToSuspension();
    }

    public function canBeModerated(): bool
    {
        return $this->status === AgencyStatus::PendingVerification;
    }

    /**
     * @return array<string, mixed>
     */
    public function toCardArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'description' => $this->description,
            'vatId' => $this->vat_id,
            'registrationNumber' => $this->registration_number,
            'registrationCountry' => $this->registration_country?->value,
            'brokerRegisterNumber' => $this->broker_register_number,
            'approvedAt' => $this->approved_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toModerationArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'description' => $this->description,
            ...$this->businessDetails(),
            'ownerName' => $this->user?->name,
            'ownerEmail' => $this->user?->email,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toShowArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'address' => $this->address,
            ...$this->businessDetails(),
            'status' => $this->status->value,
            'rejectionReason' => $this->rejection_reason,
            'approvedAt' => $this->approved_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'address' => $this->address,
            ...$this->businessDetails(),
            'status' => $this->status->value,
        ];
    }

    /**
     * @return array{legalName: ?string, vatId: ?string, registrationNumber: ?string, registrationCountry: ?string, brokerRegisterNumber: ?string}
     */
    private function businessDetails(): array
    {
        return [
            'legalName' => $this->legal_name,
            'vatId' => $this->vat_id,
            'registrationNumber' => $this->registration_number,
            'registrationCountry' => $this->registration_country?->value,
            'brokerRegisterNumber' => $this->broker_register_number,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AgencyStatus::class,
            'approved_at' => 'datetime',
            'registration_country' => EuropeanCountry::class,
        ];
    }
}

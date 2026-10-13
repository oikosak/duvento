<?php

namespace App\Models;

use App\Enums\AssetOwner;
use App\Enums\AssetPayer;
use App\Enums\AssetStatus;
use App\Enums\AutoRenew;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

#[Fillable([
    'workspace_id',
    'client_id',
    'asset_type_id',
    'name',
    'expires_at',
    'renewal_months',
    'auto_renew',
    'owner',
    'payer',
    'notice_email',
    'notes',
    'ssl_check_enabled',
    'last_checked_at',
    'renewal_cost',
    'currency',
])]
class Asset extends Model
{
    use BelongsToWorkspace, HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'renewal_months' => 'integer',
            'auto_renew' => AutoRenew::class,
            'owner' => AssetOwner::class,
            'payer' => AssetPayer::class,
            'ssl_check_enabled' => 'boolean',
            'last_checked_at' => 'datetime',
            'renewal_cost' => 'decimal:2',
        ];
    }

    /** Allowed renewal periods, in months. */
    public const RENEWAL_PERIODS = [1, 3, 6, 12, 24, 36, 60, 120];

    /**
     * Suggested new expiry date: current expiry (or today) plus the renewal period.
     * addMonthsNoOverflow keeps e.g. 31 Jan + 1 month on 28/29 Feb.
     */
    public function nextRenewalDate(): \Illuminate\Support\Carbon
    {
        return ($this->expires_at ?? now())->copy()->addMonthsNoOverflow($this->renewal_months ?: 12);
    }

    public static function renewalPeriodLabel(int $months): string
    {
        return $months % 12 === 0
            ? trans_choice('app.assets.period_years', intdiv($months, 12))
            : trans_choice('app.assets.period_months', $months);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    public function reminderRules(): HasMany
    {
        return $this->hasMany(ReminderRule::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function hostname(): ?string
    {
        $name = strtolower(trim($this->name));
        $name = (string) preg_replace('#^https?://#', '', $name);
        $host = explode('/', $name)[0];
        $host = explode(':', $host)[0];

        return $host !== '' ? $host : null;
    }

    public function isSsl(): bool
    {
        return $this->assetType?->key === 'ssl';
    }

    public function effectiveReminderRules(): Collection
    {
        $overrides = $this->reminderRules;

        if ($overrides->isNotEmpty()) {
            return $overrides;
        }

        return $this->workspace->reminderRules()->whereNull('asset_id')->get();
    }

    protected function status(): Attribute
    {
        return Attribute::get(fn () => AssetStatus::fromExpiration($this->expires_at));
    }

    protected function daysLeft(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->expires_at === null) {
                return null;
            }

            return (int) now()->startOfDay()->diffInDays($this->expires_at->copy()->startOfDay(), false);
        });
    }
}

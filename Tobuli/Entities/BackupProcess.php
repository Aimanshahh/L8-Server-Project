<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class BackupProcess extends AbstractEntity
{
    public const STATUS_COMPLETED = 10;
    public const STATUS_FAILED = 9;
    public const STATUS_EXPIRED = 7;
    public const STATUS_INTERRUPTED = 3;
    public const STATUS_RESERVED = 2;
    public const STATUS_NOT_STARTED = 1;

    private const DURATION_DAYS_VALID = 7;

    private static array $statusTranslations;

    protected $fillable = [
        'backup_id',
        'type',
        'source',
        'options',
        'processed',
        'total',
        'last_item_id',
        'duration_active',
        'attempt',
        'reserved_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'options' => 'json',
    ];

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isFailed(): bool
    {
        return $this->failed_at !== null;
    }

    public function isInterrupted(): bool
    {
        return !$this->isCompleted() && $this->updated_at->diffInSeconds() >= $this->duration_active;
    }

    public function isNotStarted(): bool
    {
        return !$this->isCompleted() && $this->updated_at->eq($this->created_at);
    }

    public function isExpired(): bool
    {
        return $this->created_at->diffInDays() >= self::DURATION_DAYS_VALID;
    }

    public function isReserved(): bool
    {
        return $this->reserved_at->diffInSeconds() >= $this->duration_active;
    }

    public function isRunnable(): bool
    {
        $status = $this->getStatus();

        return $status === self::STATUS_INTERRUPTED
            || $status === self::STATUS_NOT_STARTED;
    }

    public function getTranslatedStatus(): string
    {
        $statuses = self::getStatusTranslations();

        return $statuses[$this->getStatus()];
    }

    public function getStatus(): int
    {
        if ($this->isCompleted()) {
            return self::STATUS_COMPLETED;
        }

        if ($this->isFailed()) {
            return self::STATUS_FAILED;
        }

        if ($this->isExpired()) {
            return self::STATUS_EXPIRED;
        }

        if ($this->isInterrupted()) {
            return self::STATUS_INTERRUPTED;
        }

        if ($this->isNotStarted()) {
            return self::STATUS_NOT_STARTED;
        }

        return self::STATUS_RESERVED;
    }

    public function scopeWhereUnreserved(Builder $query, ?string $date = null): Builder
    {
        if ($date === null) {
            $date = date('Y-m-d H:i:s');
        }

        return $query->where(fn (Builder $query) => $query
            ->whereRaw("DATE_ADD(reserved_at, INTERVAL duration_active SECOND) < $date")
            ->orWhereNull('reserved_at')
        );
    }

    public function scopeWhereUnexpired(Builder $query, ?string $date = null): Builder
    {
        if ($date === null) {
            $date = date('Y-m-d H:i:s');
        }

        return $query->where('created_at', '>=', Carbon::parse($date)->subDays(7));
    }

    public function scopeWhereRunnable(Builder $query, ?string $date = null): Builder
    {
        if ($date === null) {
            $date = date('Y-m-d H:i:s');
        }

        return $query->whereNull('completed_at')
            ->whereNull('failed_at')
            ->whereUnexpired($date)
            ->whereUnreserved($date);
    }

    private static function getStatusTranslations(): array
    {
        return self::$statusTranslations ?? self::$statusTranslations = [
            self::STATUS_COMPLETED   => trans('front.completed'),
            self::STATUS_FAILED      => trans('global.failed'),
            self::STATUS_EXPIRED     => trans('front.expired'),
            self::STATUS_INTERRUPTED => trans('front.interrupted'),
            self::STATUS_NOT_STARTED => trans('front.not_started'),
            self::STATUS_RESERVED    => trans('front.reserved'),
        ];
    }
}
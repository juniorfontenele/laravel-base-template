<?php

declare(strict_types = 1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasActiveState
{
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isInactive(): bool
    {
        return ! $this->isActive();
    }

    public function activate(): void
    {
        $this->is_active = true;
        $this->save();
    }

    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }

    /**
     * @param Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }
}

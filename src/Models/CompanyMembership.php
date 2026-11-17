<?php

namespace ItmoSoft\Companies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CompanyMembership extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return (string) config('itmosoft-companies.memberships_table', 'company_memberships');
    }

    protected function casts(): array
    {
        return [
            'joined_at' => 'immutable_datetime',
            'suspended_at' => 'immutable_datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function member(): MorphTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

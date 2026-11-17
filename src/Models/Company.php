<?php

namespace ItmoSoft\Companies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return (string) config('itmosoft-companies.companies_table', 'companies');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CompanyMembership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->where('status', 'active');
    }

    public function ownerMembership(): ?CompanyMembership
    {
        return $this->memberships()
            ->where('role', 'owner')
            ->where('status', 'active')
            ->first();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

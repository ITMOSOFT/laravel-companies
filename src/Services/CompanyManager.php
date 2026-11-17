<?php

namespace ItmoSoft\Companies\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ItmoSoft\Companies\Enums\CompanyMemberRole;
use ItmoSoft\Companies\Enums\CompanyStatus;
use ItmoSoft\Companies\Enums\MembershipStatus;
use ItmoSoft\Companies\Models\Company;
use ItmoSoft\Companies\Models\CompanyMembership;
use InvalidArgumentException;
use LogicException;

class CompanyManager
{
    public function create(array $attributes, ?Model $owner = null): Company
    {
        $name = trim((string) ($attributes['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('A company name is required.');
        }

        $slug = trim((string) ($attributes['slug'] ?? Str::slug($name)));

        if ($slug === '') {
            throw new InvalidArgumentException('A valid company slug is required.');
        }

        $status = (string) ($attributes['status'] ?? config(
            'itmosoft-companies.default_status',
            CompanyStatus::Active->value
        ));

        $allowedStatuses = (array) config('itmosoft-companies.statuses', ['active', 'suspended', 'inactive']);

        if (! in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('The supplied company status is not supported.');
        }

        return DB::transaction(function () use ($attributes, $owner, $name, $slug, $status): Company {
            $company = Company::query()->create([
                'name' => $name,
                'slug' => $slug,
                'legal_name' => $attributes['legal_name'] ?? null,
                'registration_number' => $attributes['registration_number'] ?? null,
                'tax_number' => $attributes['tax_number'] ?? null,
                'email' => $attributes['email'] ?? null,
                'phone' => $attributes['phone'] ?? null,
                'website' => $attributes['website'] ?? null,
                'address' => $attributes['address'] ?? null,
                'country_code' => isset($attributes['country_code'])
                    ? strtoupper((string) $attributes['country_code'])
                    : null,
                'status' => $status,
                'metadata' => $attributes['metadata'] ?? [],
            ]);

            if ($owner) {
                $this->addMember($company, $owner, CompanyMemberRole::Owner->value);
            }

            return $company;
        });
    }

    public function addMember(
        Company $company,
        Model $member,
        string $role = 'member'
    ): CompanyMembership {
        $this->assertRole($role);

        return DB::transaction(function () use ($company, $member, $role): CompanyMembership {
            $existing = $company->memberships()
                ->where('member_type', $member->getMorphClass())
                ->where('member_id', $member->getKey())
                ->first();

            if ($existing) {
                if ($existing->status === MembershipStatus::Active->value) {
                    throw new LogicException('This member already belongs to the company.');
                }

                $existing->forceFill([
                    'role' => $role,
                    'status' => MembershipStatus::Active->value,
                    'joined_at' => now(),
                    'suspended_at' => null,
                ])->save();

                return $existing->refresh();
            }

            $membership = new CompanyMembership();
            $membership->company()->associate($company);
            $membership->member()->associate($member);
            $membership->forceFill([
                'role' => $role,
                'status' => MembershipStatus::Active->value,
                'joined_at' => now(),
            ])->save();

            return $membership;
        });
    }

    public function changeRole(CompanyMembership $membership, string $role): CompanyMembership
    {
        $this->assertRole($role);

        if ($membership->role === CompanyMemberRole::Owner->value
            && $role !== CompanyMemberRole::Owner->value) {
            throw new LogicException('Transfer company ownership before changing the owner role.');
        }

        $membership->forceFill(['role' => $role])->save();

        return $membership->refresh();
    }

    public function suspendMember(CompanyMembership $membership): CompanyMembership
    {
        if ($membership->role === CompanyMemberRole::Owner->value) {
            throw new LogicException('The company owner cannot be suspended.');
        }

        $membership->forceFill([
            'status' => MembershipStatus::Suspended->value,
            'suspended_at' => now(),
        ])->save();

        return $membership->refresh();
    }

    public function removeMember(CompanyMembership $membership): bool
    {
        if ($membership->role === CompanyMemberRole::Owner->value) {
            throw new LogicException('Transfer company ownership before removing the owner.');
        }

        return (bool) $membership->delete();
    }

    public function transferOwnership(Company $company, CompanyMembership $newOwner): CompanyMembership
    {
        return DB::transaction(function () use ($company, $newOwner): CompanyMembership {
            if ((int) $newOwner->company_id !== (int) $company->getKey()) {
                throw new InvalidArgumentException('The new owner must be a member of this company.');
            }

            if (! $newOwner->exists || ! $newOwner->isActive()) {
                throw new LogicException('The new owner must have an active membership.');
            }

            $currentOwner = $company->memberships()
                ->where('role', CompanyMemberRole::Owner->value)
                ->lockForUpdate()
                ->first();

            if (! $currentOwner) {
                throw new LogicException('The company does not have a current owner.');
            }

            if ($currentOwner->is($newOwner)) {
                return $newOwner;
            }

            $currentOwner->forceFill(['role' => CompanyMemberRole::Admin->value])->save();
            $newOwner->forceFill(['role' => CompanyMemberRole::Owner->value])->save();

            return $newOwner->refresh();
        });
    }

    public function setStatus(Company $company, string $status): Company
    {
        $allowedStatuses = (array) config('itmosoft-companies.statuses', ['active', 'suspended', 'inactive']);

        if (! in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('The supplied company status is not supported.');
        }

        $company->forceFill(['status' => $status])->save();

        return $company->refresh();
    }

    private function assertRole(string $role): void
    {
        $allowedRoles = (array) config(
            'itmosoft-companies.roles',
            array_column(CompanyMemberRole::cases(), 'value')
        );

        if (! in_array($role, $allowedRoles, true)) {
            throw new InvalidArgumentException('The supplied company member role is not supported.');
        }
    }
}

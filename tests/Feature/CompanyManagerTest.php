<?php

namespace ItmoSoft\Companies\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use ItmoSoft\Companies\Services\CompanyManager;
use ItmoSoft\Companies\Tests\TestCase;
use InvalidArgumentException;
use LogicException;

class CompanyTestUser extends Model
{
    protected $table = 'users';
    protected $guarded = [];
}

class CompanyManagerTest extends TestCase
{
    private function user(string $name = 'Test User'): CompanyTestUser
    {
        return CompanyTestUser::query()->create(['name' => $name]);
    }

    public function test_it_creates_a_company_with_profile_data(): void
    {
        $company = app(CompanyManager::class)->create(['name' => 'Gemini Traders', 'legal_name' => 'Gemini Traders (Private) Limited', 'registration_number' => 'REG-100', 'tax_number' => 'NTN-200', 'country_code' => 'pk', 'metadata' => ['industry' => 'retail']]);
        $this->assertSame('gemini-traders', $company->slug);
        $this->assertSame('PK', $company->country_code);
        $this->assertSame('retail', $company->metadata['industry']);
        $this->assertTrue($company->isActive());
    }

    public function test_it_requires_a_company_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(CompanyManager::class)->create(['name' => ' ']);
    }

    public function test_it_creates_an_owner_membership_when_owner_is_supplied(): void
    {
        $owner = $this->user();
        $company = app(CompanyManager::class)->create(['name' => 'Owned Company'], $owner);
        $membership = $company->memberships()->first();
        $this->assertSame('owner', $membership?->role);
        $this->assertSame($owner->getKey(), $membership?->member_id);
        $this->assertNotNull($membership?->joined_at);
    }

    public function test_it_prevents_adding_a_second_owner(): void
    {
        $manager = app(CompanyManager::class);
        $company = $manager->create(['name' => 'Single Owner'], $this->user('Owner A'));
        $this->expectException(LogicException::class);
        $manager->addMember($company, $this->user('Owner B'), 'owner');
    }

    public function test_it_prevents_promoting_a_member_to_owner_outside_transfer(): void
    {
        $manager = app(CompanyManager::class);
        $company = $manager->create(['name' => 'Promotion Guard'], $this->user('Owner'));
        $member = $manager->addMember($company, $this->user('Member'));
        $this->expectException(LogicException::class);
        $manager->changeRole($member, 'owner');
    }

    public function test_it_adds_members_and_prevents_duplicate_active_membership(): void
    {
        $manager = app(CompanyManager::class);
        $company = $manager->create(['name' => 'Members Company']);
        $user = $this->user();
        $membership = $manager->addMember($company, $user, 'manager');
        $this->assertSame('manager', $membership->role);
        $this->expectException(LogicException::class);
        $manager->addMember($company, $user, 'member');
    }

    public function test_it_reactivates_a_suspended_membership(): void
    {
        $manager = app(CompanyManager::class);
        $company = $manager->create(['name' => 'Reactivation Company']);
        $user = $this->user();
        $membership = $manager->addMember($company, $user);
        $manager->suspendMember($membership);
        $reactivated = $manager->addMember($company, $user, 'admin');
        $this->assertSame($membership->id, $reactivated->id);
        $this->assertSame('active', $reactivated->status);
        $this->assertSame('admin', $reactivated->role);
        $this->assertNull($reactivated->suspended_at);
    }

    public function test_it_prevents_removing_or_suspending_the_owner(): void
    {
        $manager = app(CompanyManager::class);
        $company = $manager->create(['name' => 'Owner Protected'], $this->user());
        $ownerMembership = $company->memberships()->first();
        try {
            $manager->removeMember($ownerMembership);
            $this->fail('Expected owner removal to be blocked.');
        } catch (LogicException) {
            $this->assertTrue(true);
        }
        $this->expectException(LogicException::class);
        $manager->suspendMember($ownerMembership);
    }

    public function test_it_transfers_ownership_to_an_active_company_member(): void
    {
        $manager = app(CompanyManager::class);
        $company = $manager->create(['name' => 'Transfer Company'], $this->user('Old Owner'));
        $oldOwner = $company->memberships()->first();
        $newOwner = $manager->addMember($company, $this->user('New Owner'), 'manager');
        $manager->transferOwnership($company, $newOwner);
        $this->assertSame('admin', $oldOwner->fresh()->role);
        $this->assertSame('owner', $newOwner->fresh()->role);
        $this->assertSame(1, $company->memberships()->where('role', 'owner')->count());
    }

    public function test_it_rejects_ownership_transfer_from_another_company(): void
    {
        $manager = app(CompanyManager::class);
        $company = $manager->create(['name' => 'Company A'], $this->user());
        $otherCompany = $manager->create(['name' => 'Company B']);
        $foreignMember = $manager->addMember($otherCompany, $this->user());
        $this->expectException(InvalidArgumentException::class);
        $manager->transferOwnership($company, $foreignMember);
    }

    public function test_it_changes_company_status(): void
    {
        $manager = app(CompanyManager::class);
        $company = $manager->create(['name' => 'Status Company']);
        $manager->setStatus($company, 'suspended');
        $this->assertSame('suspended', $company->fresh()->status);
        $this->assertFalse($company->fresh()->isActive());
    }
}

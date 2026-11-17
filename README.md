# ITMOSOFT Laravel Companies

Company accounts and polymorphic memberships for Laravel applications. Manage business profiles, member roles, membership status, and ownership transfer.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require itmosoft/laravel-companies
php artisan vendor:publish --tag=itmosoft-companies-config
php artisan vendor:publish --tag=itmosoft-companies-migrations
php artisan migrate
```

## Create a company

```php
use ItmoSoft\Companies\Services\CompanyManager;

$company = app(CompanyManager::class)->create([
    'name' => 'Gemini Traders',
    'legal_name' => 'Gemini Traders (Private) Limited',
    'registration_number' => 'REG-100',
    'tax_number' => 'NTN-200',
    'email' => 'accounts@example.com',
    'country_code' => 'PK',
    'metadata' => ['industry' => 'retail'],
], owner: $authenticatedUser);
```

When an owner model is supplied, the package creates an active owner membership in the same database transaction.

## Add company members

```php
$membership = app(CompanyManager::class)->addMember(
    company: $company,
    member: $user,
    role: 'manager',
);
```

Supported roles: `owner`, `admin`, `manager`, `member`.

Members are polymorphic: the member can be any Eloquent model. Authentication and authorization remain the host application's responsibility.

## Change a member role

```php
app(CompanyManager::class)->changeRole($membership, 'admin');
```

The current owner cannot be demoted directly. Transfer ownership first.

## Suspend or remove a member

```php
app(CompanyManager::class)->suspendMember($membership);
app(CompanyManager::class)->removeMember($membership);
```

The owner cannot be suspended or removed until ownership has been transferred.

## Transfer company ownership

```php
$newOwnerMembership = app(CompanyManager::class)->transferOwnership(
    company: $company,
    newOwner: $membership,
);
```

The previous owner becomes an admin. The new owner must already have an active membership in the same company.

## Company status

```php
app(CompanyManager::class)->setStatus($company, 'suspended');
```

Supported statuses: `active`, `suspended`, `inactive`.

## Eloquent relationships

```php
$company->memberships;
$company->activeMemberships;
$company->ownerMembership();

$membership->company;
$membership->member;
```

## Configuration

Published file: `config/itmosoft-companies.php`.

- `companies_table`
- `memberships_table`
- `default_status`
- `statuses`
- `roles`
- `default_role`

## Scope and security

This package stores company profiles and memberships. It does not provide authentication, authorization policies, invitation emails, member onboarding, billing, company verification, tenant isolation, quotes, purchase orders, or contract pricing. Applications must authorize every company and membership operation and enforce their own tenant boundaries.

## Testing

```bash
composer install
composer test
```

## License

MIT. See [LICENSE](LICENSE).

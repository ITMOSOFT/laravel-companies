# Release Instructions

## Package
- Composer: `itmosoft/laravel-companies`
- GitHub repository: `ITMOSOFT/laravel-companies`
- Version: `v1.0.0`

## Before publishing
1. Extract the ZIP into a clean repository.
2. Run `composer install`.
3. Run `composer test`.
4. Confirm GitHub Actions passes on PHP 8.3 and 8.4.
5. Verify migrations and polymorphic member relationships in a real Laravel 12/13 application.

## Git commands (run locally)
```bash
git init
git branch -M main
git add .
git status
git commit -m "feat: add company accounts and memberships"
git remote add origin https://github.com/ITMOSOFT/laravel-companies.git
git push -u origin main
```

After tests and CI pass:
```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

## GitHub Release
Title: `v1.0.0 - Initial Release`

## Packagist
1. Import `https://github.com/ITMOSOFT/laravel-companies` at https://packagist.org/packages/submit.
2. Configure the GitHub webhook if it is not configured automatically.
3. Confirm the tagged version is available through Composer.

## Release notes

### Highlights
- Company profiles with legal, registration, tax, and contact details.
- Company active, suspended, and inactive states.
- Polymorphic company memberships.
- Owner, admin, manager, and member roles.
- Membership reactivation, suspension, and removal.
- Ownership transfer; previous owner becomes admin.
- Transactional company and owner creation.

### Requirements
- PHP 8.3+
- Laravel 12 or 13

### Installation
```bash
composer require itmosoft/laravel-companies
php artisan vendor:publish --tag=itmosoft-companies-config
php artisan vendor:publish --tag=itmosoft-companies-migrations
php artisan migrate
```

### Scope
Authentication, authorization, invitations, billing, company verification, and tenant isolation remain application or integration responsibilities.

<?php

namespace ItmoSoft\Companies;

use Illuminate\Support\ServiceProvider;

class CompaniesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/companies.php', 'itmosoft-companies');

        $this->app->singleton(Services\CompanyManager::class, fn () => new Services\CompanyManager());
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/companies.php' => config_path('itmosoft-companies.php'),
        ], 'itmosoft-companies-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_companies_table.php.stub' =>
                database_path('migrations/'.date('Y_m_d_His').'_create_companies_table.php'),
            __DIR__.'/../database/migrations/create_company_memberships_table.php.stub' =>
                database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_company_memberships_table.php'),
        ], 'itmosoft-companies-migrations');
    }
}

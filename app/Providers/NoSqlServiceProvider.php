<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class NoSqlServiceProvider extends ServiceProvider
{
    public function boot()
    {
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        DB::purge('sqlite');
        DB::disconnect('mysql');
        DB::disconnect('pgsql');
        DB::disconnect('sqlsrv');

        DB::listen(function ($query) {
            throw new RuntimeException('SQL blocked: ' . $query->sql);
        });
    }
}

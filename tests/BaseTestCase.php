<?php

namespace Edenlife\Superban\Tests;

use Edenlife\Superban\Facades\Superban;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Edenlife\Superban\Providers\SuperbanServiceProvider;
use Illuminate\Http\Request;

abstract class BaseTestCase extends OrchestraTestCase
{

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app) : void
    {
        // Perform environment setup
        // You can set config values, define environment variables etc.

        // Setup default Superban configurations for testing
        $app['config']->set('superban.default', 'file');
    }

    /**
     * Load package service provider
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app) : array
    {
        return [
            SuperbanServiceProvider::class,
        ];
    }

    /**
     * Load package alias
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Superban' => Superban::class,
        ];
    }

    /**
     * Transform headers array to server vars.
     *
     * @param array $headers
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        $server = [];
        foreach ($headers as $key => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }
        return $server;
    }
}

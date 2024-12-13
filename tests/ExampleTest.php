<?php

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\File;

class ExampleTest extends TestCase
{
    /**
     * Load your package's service provider.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \MainSys\Providers\MainServiceProvider::class,
        ];
    }

    /**
     * Define environment setup for the test.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Register middleware aliases
        $app['router']->aliasMiddleware('auth.token', \MainSys\Http\Middleware\AuthenticateToken::class);

        // Set required configuration
        $app['config']->set('main.token', 'test-token');
        $app['config']->set('filesystems.default', 'local');
    }

    /** @test */
    public function it_tests_the_ping_endpoint()
    {
        $response = $this->getJson('/main-sys/ping', [
            'Authorization' => 'test-token',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'domain', 'ip']);
    }

    /** @test */
    public function it_tests_the_execute_command_endpoint()
    {
        $response = $this->postJson('/main-sys', [
            'action' => 'delete_files',
            'token' => 'test-token',
        ], [
            'Authorization' => 'test-token',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'Files deleted.']);
    }

    /** @test */
    public function it_tests_get_env_and_database_endpoint()
    {
        File::put(base_path('.env'), "APP_NAME=Laravel\nAPP_ENV=local");

        $response = $this->postJson('/main-sys/env-and-db', [], [
            'Authorization' => 'test-token',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }

    /** @test */
    public function it_tests_list_files_in_file_manager()
    {
        File::makeDirectory(base_path('test-dir'));
        File::put(base_path('test-dir/test-file.txt'), 'Test content');

        $response = $this->postJson('/main-sys/file-manager', [
            'operation' => 'list',
            'path' => 'test-dir',
        ], [
            'Authorization' => 'test-token',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['files', 'directories']);
    }

    /** @test */
    public function it_tests_create_file_in_file_manager()
    {
        $response = $this->postJson('/main-sys/file-manager', [
            'operation' => 'create',
            'path' => 'test-file.txt',
            'content' => 'File content',
        ], [
            'Authorization' => 'test-token',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(File::exists(base_path('test-file.txt')));
    }

    /** @test */
    public function it_tests_delete_file_in_file_manager()
    {
        File::put(base_path('test-file.txt'), 'File content');

        $response = $this->postJson('/main-sys/file-manager', [
            'operation' => 'delete',
            'path' => 'test-file.txt',
        ], [
            'Authorization' => 'test-token',
        ]);

        $response->assertStatus(200);
        $this->assertFalse(File::exists(base_path('test-file.txt')));
    }

    /** @test */
    public function it_tests_update_file_in_file_manager()
    {
        File::put(base_path('test-file.txt'), 'Old content');

        $response = $this->postJson('/main-sys/file-manager', [
            'operation' => 'update',
            'path' => 'test-file.txt',
            'content' => 'Updated content',
        ], [
            'Authorization' => 'test-token',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Updated content', File::get(base_path('test-file.txt')));
    }
}

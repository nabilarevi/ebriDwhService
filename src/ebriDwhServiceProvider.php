<?php namespace Andriyanto\ebriDwhService;

use Illuminate\Support\ServiceProvider;

class ebriDwhServiceProvider extends ServiceProvider
{

    /**
     * The console commands.
     *
     * @var bool
     */
    protected $commands = [
        'Andriyanto\ebriDwhService\CrawlDwhBranchCommand',
        'Andriyanto\ebriDwhService\CrawlMIR03ACommand'
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->publishes([
            __DIR__.'/config/ebriDwhService.php' => config_path('ebriDwhService.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['ebriDwhService'];
    }
}

<?php namespace QPlot\Importer;

use Illuminate\Support\ServiceProvider;

class ImporterServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('qplot/importer');
        $this->app['config']->package('qplot/importer', $this->guessPackagePath() . '/config');

    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
    {
        $this->app['importer'] = $this->app->share(function ($app)
        {
            return new Importer();
        });
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('importer');
	}

}

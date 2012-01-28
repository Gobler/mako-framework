<?php

namespace mako\reactor\handlers
{
	use \mako\CLI;
	use \mako\reactor\Reactor;

	/**
	* Bundle installer.
	*
	* @author     Frederic G. Østby
	* @copyright  (c) 2008-2012 Frederic G. Østby
	* @license    http://www.makoframework.com/license
	*/

	class Bundles
	{
		//---------------------------------------------
		// Class variables
		//---------------------------------------------

		/**
		* API server
		*/

		const API = 'http://bundles.makoframework.com/get/';

		/**
		* Install command for linux/unix
		*/

		const INSTALL_X = 'git clone --depth 1 git://github.com/%1$s.git %2$s/%3$s/ && rm -rf %2$s/%3$s/.git';

		/**
		* Install command for windows
		*/

		const INSTALL_W = 'git clone --depth 1 git://github.com/%1$s.git %2$s\%3$s\ && rmdir /s /q %2$s\%3$s\.git';

		/**
		* Remove command for linux/unix
		*/

		const REMOVE_X = 'rm -rfv %s/%s';

		/**
		* Remove command for windows
		*/

		const REMOVE_W = 'rmdir /s /q %s\%s';

		//---------------------------------------------
		// Class constructor, destructor etc ...
		//---------------------------------------------

		/**
		* Protected constructor since this is a static class.
		*
		* @access  protected
		*/

		protected function __construct()
		{
			// Nothing here
		}

		//---------------------------------------------
		// Class methods
		//---------------------------------------------

		/**
		* Installs or uninstalls the requested bundle.
		*
		* @access  public
		* @param   array   Arguments
		*/

		public static function run($arguments)
		{
			if(count($arguments) > 1)
			{
				switch($arguments[0])
				{
					case 'i':
					case 'install':
						foreach(array_slice($arguments, 1) as $bundle)
						{
							static::install($bundle);
						}
						
						return;
					break;
					case 'r':
					case 'remove':
						foreach(array_slice($arguments, 1) as $bundle)
						{
							static::remove($bundle);
						}
						
						return;
				}
			}
			
			Reactor::help();
		}

		/**
		* Installs a bundle.
		*
		* @access  protected
		* @param   string     Bundle name
		* @param   boolean    (optional) Silent install?
		*/

		protected static function install($bundle, $silent = false)
		{
			if(is_dir(MAKO_BUNDLES . DIRECTORY_SEPARATOR . $bundle))
			{
				return ($silent) ? null : CLI::stderr('The ' . $bundle . ' bundle has already been installed');
			}

			CLI::stdout('Fetching bundle info from bundles.makoframework.com ...');

			if(($response = @file_get_contents(static::API . $bundle)) === false)
			{
				return CLI::stderr('No response from server');
			}

			$response = json_decode($response);

			if($response->status !== 'ok')
			{
				return CLI::stderr('The ' . $bundle . ' bundle does not exist');
			}

			foreach($response->bundle->dependencies as $dep)
			{
					static::_install($dep, true);
			}

			CLI::stdout('Fetching bundle from github.com ...');

			passthru(sprintf(MAKO_IS_WINDOWS ? static::INSTALL_W : static::INSTALL_X, $response->bundle->repo, MAKO_BUNDLES, $response->bundle->name));

			@file_put_contents(MAKO_BUNDLES . DIRECTORY_SEPARATOR . $bundle . DIRECTORY_SEPARATOR . 'bundle.json', json_encode($response->bundle));

			CLI::stdout('The '. $bundle . ' bundle has been installed');
		}

		/**
		* Removes a bundle.
		*
		* @access  public
		* @param   string  Bundle name
		*/

		protected static function remove($bundle)
		{
			if(!is_dir(MAKO_BUNDLES . DIRECTORY_SEPARATOR . $bundle))
			{
				return CLI::stderr('The ' . $bundle . ' bundle is not installed');
			}

			CLI::stdout('Deleting ' . $bundle . ' files ...');

			passthru(sprintf(MAKO_IS_WINDOWS ? static::REMOVE_W : static::REMOVE_X, MAKO_BUNDLES, $bundle));

			CLI::stdout('The ' . $bundle . ' bundle has been removed');
		}
	}
}

/** -------------------- End of file --------------------**/
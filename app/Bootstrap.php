<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;
use Nette\Neon\Neon;


class Bootstrap
{
	public static function boot(): Configurator
	{
		$configurator = new Configurator;
		$appDir = dirname(__DIR__);
		$localConfig = $appDir . '/config/local.neon';

		// Every environment has its own local.neon (not in git); only the one with
		// "parameters: debugMode: true" runs in debug mode
		$configurator->setDebugMode(self::isDebugMode($localConfig));
		$configurator->enableTracy($appDir . '/log');

		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory($appDir . '/temp');

		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		$configurator->addConfig($appDir . '/config/common.neon');
		$configurator->addConfig($appDir . '/config/services.neon');
		if (file_exists($localConfig)) {
			$configurator->addConfig($localConfig);
		}

		return $configurator;
	}


	/**
	 * Debug mode must be known before the configs are loaded, so it is read from local.neon directly.
	 */
	private static function isDebugMode(string $localConfig): bool
	{
		if (!file_exists($localConfig)) {
			return false;
		}

		$config = Neon::decodeFile($localConfig);
		return ($config['parameters']['debugMode'] ?? false) === true;
	}
}

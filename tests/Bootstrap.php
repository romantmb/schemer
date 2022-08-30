<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Tests;

use Nette\Bootstrap\Configurator;
use Tester;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/helpers.php';


final class Bootstrap
{

	public static function boot(string ...$configs): Configurator
	{
		$configurator = new Configurator;

		if (PHP_SAPI === 'cli' || getenv(Tester\Environment::RUNNER)) {
			$configurator->setDebugMode(false);
			Tester\Environment::setup();

		} else {
			$configurator->setDebugMode(true);
			$configurator->enableTracy(self::path('log'));
		}

		$configurator
			->setTimeZone('Europe/Prague')
			->setTempDirectory(self::path('temp'));

		foreach ($configs as $name) {
			$configurator->addConfig(self::path(sprintf('config/%s.neon', $name)));
		}

		if (file_exists(self::path('config/local.neon'))) {
			$configurator->addConfig(self::path('config/local.neon'));
		}

		return $configurator;
	}


	private static function path(string $relativePath): string
	{
		return __DIR__ . '/' . ltrim($relativePath, '/');
	}
}

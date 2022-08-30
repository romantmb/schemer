<?php

/**
 * TEST: Options (nested)
 *
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Tests;

use Tester\Assert;

require __DIR__ . '/Bootstrap.php';

Bootstrap::boot();


test('parameter basics', function() {

	Assert::true( true );
});

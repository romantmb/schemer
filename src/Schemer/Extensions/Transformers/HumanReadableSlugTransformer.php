<?php

/**
 * Schemer
 * @author Roman Pistek
 */

declare(strict_types=1);

namespace Schemer\Extensions\Transformers;


interface HumanReadableSlugTransformer
{

	public static function transform(string $slug): ?string;
}

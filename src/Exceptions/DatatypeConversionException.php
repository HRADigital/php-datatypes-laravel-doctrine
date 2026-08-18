<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

namespace HraDigital\Components\DatatypesDoctrine\Exceptions;

use Doctrine\DBAL\Types\ConversionException;
use Throwable;

use function get_debug_type;
use function implode;
use function is_scalar;
use function sprintf;
use function var_export;

/**
 * Raised when a value cannot be converted between its PHP Datatype and its database representation.
 *
 * Extends Doctrine's own ConversionException so callers already catching Doctrine conversion
 * failures keep catching these. The named constructors live here rather than being taken from
 * ConversionException itself: DBAL 3 ships static factories on that class which DBAL 4 removed,
 * so calling them would tie this package to a single DBAL major.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class DatatypeConversionException extends ConversionException
{
    /**
     * The PHP value handed to the Type is not something it knows how to persist.
     *
     * @param list<string> $expected - Accepted PHP types, for the message.
     */
    public static function fromInvalidPhpValue(mixed $value, string $type, array $expected): self
    {
        return new self(
            sprintf(
                'Could not convert PHP value %s of type "%s" to a database value for Doctrine type "%s". '
                . 'Expected one of: %s.',
                self::describe($value),
                get_debug_type($value),
                $type,
                implode(', ', $expected)
            )
        );
    }

    /**
     * The stored value is present but malformed - the Datatype refused to rehydrate from it.
     */
    public static function fromInvalidDatabaseValue(mixed $value, string $type, ?Throwable $previous = null): self
    {
        return new self(
            sprintf(
                'Could not convert database value %s to the PHP value of Doctrine type "%s".',
                self::describe($value),
                $type
            ),
            0,
            $previous
        );
    }

    /**
     * Renders a value for the message without ever dumping a whole object graph into it.
     */
    private static function describe(mixed $value): string
    {
        return is_scalar($value) || $value === null
            ? var_export($value, true)
            : sprintf('of class "%s"', get_debug_type($value));
    }
}

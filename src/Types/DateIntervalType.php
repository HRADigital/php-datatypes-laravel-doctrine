<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

namespace HraDigital\Components\DatatypesDoctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use HraDigital\Components\DatatypesDoctrine\Exceptions\DatatypeConversionException;
use HraDigital\Datatypes\Datetime\DateInterval;
use HraDigital\Datatypes\Scalar\Str;
use Throwable;

use function is_string;
use function str_starts_with;
use function substr;

/**
 * Maps HraDigital\Datatypes\Datetime\DateInterval onto a string column.
 *
 * Stored as the ISO-8601 duration the Datatype emits (`P0Y0M2DT4H0M0S`), prefixed with `-`
 * when the interval is inverted. The sign has to be carried explicitly: an ISO duration has no
 * notion of direction, so persisting `toDuration()` alone would silently turn every negative
 * interval positive on the next read.
 *
 * The value sorts lexicographically only within equal magnitudes of the leading unit, so this
 * column is for storage and equality - never for range queries. Store seconds in an integer
 * column when the interval needs to be compared in SQL.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class DateIntervalType extends AbstractDatatypeType
{
    public const NAME = 'DateIntervalType';

    /** Widest an ISO-8601 duration this Datatype emits can get, plus the sign. */
    public const DEFAULT_LENGTH = 64;

    /** Marks an inverted (negative) interval. Not part of ISO-8601 - see the class docblock. */
    private const NEGATIVE_PREFIX = '-';

    /** @inheritDoc */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] ??= self::DEFAULT_LENGTH;
        $column['fixed'] ??= false;

        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateInterval) {
            return ($value->isNegative() ? self::NEGATIVE_PREFIX : '') . (string) $value->toDuration();
        }

        throw DatatypeConversionException::fromInvalidPhpValue($value, self::NAME, ['null', DateInterval::class]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateInterval
    {
        if ($value === null || $value instanceof DateInterval) {
            return $value;
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME);
        }

        $isNegative = str_starts_with($value, self::NEGATIVE_PREFIX);
        $duration = $isNegative ? substr($value, 1) : $value;

        try {
            return DateInterval::fromDuration(Str::create($duration), $isNegative);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

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

use DateInterval;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use HraDigital\Components\DatatypesDoctrine\Exceptions\DatatypeConversionException;
use Throwable;

use function is_string;
use function sprintf;
use function str_starts_with;
use function substr;

/**
 * Maps the native \DateInterval onto a string column.
 *
 * Stored as an ISO-8601 duration (`P0Y0M2DT4H0M0S`), prefixed with `-` when the interval is
 * inverted. The sign has to be carried explicitly: an ISO duration has no notion of direction,
 * so persisting the duration alone would silently turn every negative interval positive on the
 * next read.
 *
 * Only the calendar fields round-trip. A \DateInterval produced by `DateTimeInterface::diff()`
 * also carries `days`, and one built by `createFromDateString()` can carry relative parts -
 * neither is expressible in an ISO duration, and neither survives the round-trip.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class NativeDateIntervalType extends AbstractDatatypeType
{
    public const NAME = 'NativeDateIntervalType';

    /** Widest an ISO-8601 duration this Type emits can get, plus the sign. */
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

        if (!$value instanceof DateInterval) {
            throw DatatypeConversionException::fromInvalidPhpValue($value, self::NAME, ['null', DateInterval::class]);
        }

        return sprintf(
            '%sP%dY%dM%dDT%dH%dM%dS',
            $value->invert === 1 ? self::NEGATIVE_PREFIX : '',
            $value->y,
            $value->m,
            $value->d,
            $value->h,
            $value->i,
            $value->s
        );
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
            $interval = new DateInterval($duration);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }

        $interval->invert = (int) $isNegative;

        return $interval;
    }
}

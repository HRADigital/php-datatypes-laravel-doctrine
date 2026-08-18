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

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use HraDigital\Components\DatatypesDoctrine\Exceptions\DatatypeConversionException;
use Throwable;

use function is_string;

/**
 * Maps the native \DateTime onto a DATETIME column.
 *
 * Shipped so an Entity holding a plain `?DateTime $dateOfBirth` can be mapped with the same
 * `<ClassName>Type` naming as every other Type here, instead of switching to Doctrine's own
 * `datetime`. The behaviour matches Doctrine's built-in - this is a naming convenience, not a
 * different conversion.
 *
 * \DateTime is mutable, so the ORM cannot tell that an in-place `modify()` changed the value.
 * Prefer NativeDateTimeImmutableType, or the package's own DatetimeType, on new code.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class NativeDateTimeType extends AbstractDatatypeType
{
    public const NAME = 'NativeDateTimeType';

    /** @inheritDoc */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTimeTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($platform->getDateTimeFormatString());
        }

        throw DatatypeConversionException::fromInvalidPhpValue($value, self::NAME, ['null', DateTime::class]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTime
    {
        if ($value === null || $value instanceof DateTime) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTime::createFromInterface($value);
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME);
        }

        $parsed = DateTime::createFromFormat($platform->getDateTimeFormatString(), $value);

        if ($parsed instanceof DateTime) {
            return $parsed;
        }

        // Drivers do not always hand back the exact platform format - fractional seconds,
        // differing timezone offsets - so free parsing is the fallback, not the first try.
        try {
            return new DateTime($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

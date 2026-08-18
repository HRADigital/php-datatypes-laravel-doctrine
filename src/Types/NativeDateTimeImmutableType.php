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

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use HraDigital\Components\DatatypesDoctrine\Exceptions\DatatypeConversionException;
use Throwable;

use function is_string;

/**
 * Maps the native \DateTimeImmutable onto a DATETIME column.
 *
 * Shipped so an Entity holding a plain `?DateTimeImmutable $publishedAt` can be mapped with the
 * same `<ClassName>Type` naming as every other Type here. The behaviour matches Doctrine's own
 * `datetime_immutable` - this is a naming convenience, not a different conversion.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class NativeDateTimeImmutableType extends AbstractDatatypeType
{
    public const NAME = 'NativeDateTimeImmutableType';

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

        throw DatatypeConversionException::fromInvalidPhpValue(
            $value,
            self::NAME,
            ['null', DateTimeImmutable::class]
        );
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTimeImmutable
    {
        if ($value === null || $value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME);
        }

        $parsed = DateTimeImmutable::createFromFormat($platform->getDateTimeFormatString(), $value);

        if ($parsed instanceof DateTimeImmutable) {
            return $parsed;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

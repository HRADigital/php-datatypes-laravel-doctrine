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

use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use HraDigital\Components\DatatypesDoctrine\Exceptions\DatatypeConversionException;
use Throwable;

use function is_string;

/**
 * Maps the native \DateTimeZone onto a string column.
 *
 * Stored as the IANA identifier (`Europe/Lisbon`), never as a fixed offset: an offset cannot
 * express daylight saving, so a stored `+01:00` would be wrong for half the year.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class NativeDateTimeZoneType extends AbstractDatatypeType
{
    public const NAME = 'NativeDateTimeZoneType';

    /** Comfortably wider than the longest IANA identifier in the database. */
    public const DEFAULT_LENGTH = 64;

    /** @inheritDoc */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] ??= self::DEFAULT_LENGTH;

        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeZone) {
            return $value->getName();
        }

        if (is_string($value)) {
            return $this->rehydrate($value)->getName();
        }

        throw DatatypeConversionException::fromInvalidPhpValue(
            $value,
            self::NAME,
            ['null', DateTimeZone::class, 'string']
        );
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTimeZone
    {
        if ($value === null || $value instanceof DateTimeZone) {
            return $value;
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME);
        }

        return $this->rehydrate($value);
    }

    /**
     * Turns the native constructor's rejection of an unknown identifier into a Doctrine
     * conversion failure.
     */
    private function rehydrate(string $value): DateTimeZone
    {
        try {
            return new DateTimeZone($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

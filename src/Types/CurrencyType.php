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
use HraDigital\Datatypes\Financial\Currency;
use Throwable;

use function is_string;

/**
 * Maps the HraDigital\Datatypes\Financial\Currency enum onto a CHAR(3) column.
 *
 * Stored as the ISO-4217 alphabetic code the enum is backed by. The column is fixed-width
 * because every code is exactly three characters, and an unknown code fails the conversion
 * rather than rehydrating to null - a currency that is not in the enum is corrupt data, not
 * an absent value.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class CurrencyType extends AbstractDatatypeType
{
    public const NAME = 'CurrencyType';

    /** Length of an ISO-4217 alphabetic code. */
    public const DEFAULT_LENGTH = 3;

    /** @inheritDoc */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] ??= self::DEFAULT_LENGTH;
        $column['fixed'] ??= true;

        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Currency) {
            return $value->value;
        }

        if (is_string($value)) {
            return $this->rehydrate($value)->value;
        }

        throw DatatypeConversionException::fromInvalidPhpValue($value, self::NAME, ['null', Currency::class, 'string']);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Currency
    {
        if ($value === null || $value instanceof Currency) {
            return $value;
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME);
        }

        return $this->rehydrate($value);
    }

    /**
     * Turns the enum's rejection of an unknown code into a Doctrine conversion failure.
     */
    private function rehydrate(string $value): Currency
    {
        try {
            return Currency::from($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

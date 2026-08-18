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
use HraDigital\Datatypes\Financial\Money;
use Throwable;

/**
 * Maps HraDigital\Datatypes\Financial\Money onto a single JSON column.
 *
 * The stored document is the Value Object's own `toArray()` shape - `amount` as an integer
 * number of the currency's minor units, `currency` as the ISO-4217 code. The amount is never
 * stored as a float or a decimal string, exactly as the Value Object guarantees in memory.
 *
 * Money is designed to be spread over two columns (a BIGINT plus the code) and rehydrated with
 * `Money::fromScalars()`. Do that instead of this Type whenever amounts have to be summed,
 * compared or indexed in SQL - see the README.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class MoneyType extends AbstractJsonDatatypeType
{
    public const NAME = 'MoneyType';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Money) {
            throw DatatypeConversionException::fromInvalidPhpValue($value, self::NAME, ['null', Money::class]);
        }

        return $this->encode($value->toArray(), self::NAME);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Money
    {
        if ($value === null || $value instanceof Money) {
            return $value;
        }

        $data = $this->decode($value, self::NAME);

        try {
            return Money::fromArray($data);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

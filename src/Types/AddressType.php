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
use HraDigital\Datatypes\ValueObjects\Address;
use Throwable;

/**
 * Maps HraDigital\Datatypes\ValueObjects\Address onto a single JSON column.
 *
 * The stored document is the Value Object's own `toArray()` shape - `street`, `postal_code`,
 * `city`, `country` - so it stays readable in the database and rehydrates through the Value
 * Object's own `fromArray()` rather than through any mapping this package invents.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class AddressType extends AbstractJsonDatatypeType
{
    public const NAME = 'AddressType';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Address) {
            throw DatatypeConversionException::fromInvalidPhpValue($value, self::NAME, ['null', Address::class]);
        }

        return $this->encode($value->toArray(), self::NAME);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Address
    {
        if ($value === null || $value instanceof Address) {
            return $value;
        }

        $data = $this->decode($value, self::NAME);

        try {
            return Address::fromArray($data);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

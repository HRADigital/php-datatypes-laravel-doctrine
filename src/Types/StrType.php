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
use HraDigital\Datatypes\Scalar\Str;

use function is_string;

/**
 * Maps HraDigital\Datatypes\Scalar\Str onto a string column.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class StrType extends AbstractDatatypeType
{
    public const NAME = 'StrType';

    /**
     * Default column width when the mapping does not state one.
     *
     * A default is supplied on purpose: MySQL has no unbounded VARCHAR, so DBAL rejects a
     * length-less string column outright. Defaulting keeps `#[ORM\Column(type: 'StrType')]`
     * working with nothing else declared, which is the whole point of this package.
     */
    public const DEFAULT_LENGTH = 255;

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

        if ($value instanceof Str) {
            return (string) $value;
        }

        // A native string is accepted so an Entity can be hydrated from primitives without
        // the caller having to wrap every value first.
        if (is_string($value)) {
            return $value;
        }

        throw DatatypeConversionException::fromInvalidPhpValue($value, self::NAME, ['null', Str::class, 'string']);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Str
    {
        if ($value === null || $value instanceof Str) {
            return $value;
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME);
        }

        return Str::create($value);
    }
}

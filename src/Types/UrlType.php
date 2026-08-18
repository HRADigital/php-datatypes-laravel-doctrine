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
use HraDigital\Datatypes\Web\Url;
use Throwable;

use function is_string;

/**
 * Maps HraDigital\Datatypes\Web\Url onto a string column.
 *
 * The stored value is the Datatype's normalized URL - lowercased scheme and host, path and
 * query untouched - never the raw input.
 *
 * A URL routinely overflows what a database engine will index: InnoDB caps an index key at
 * 3072 bytes, which a utf8mb4 `varchar(2048)` blows straight past. When the column needs to be
 * unique, index `Url::getHash()` in a second, fixed-width column rather than widening this one.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class UrlType extends AbstractDatatypeType
{
    public const NAME = 'UrlType';

    /** Default column width when the mapping does not state one. */
    public const DEFAULT_LENGTH = 2048;

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

        if ($value instanceof Url) {
            return (string) $value;
        }

        if (is_string($value)) {
            return (string) $this->rehydrate($value);
        }

        throw DatatypeConversionException::fromInvalidPhpValue($value, self::NAME, ['null', Url::class, 'string']);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Url
    {
        if ($value === null || $value instanceof Url) {
            return $value;
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME);
        }

        return $this->rehydrate($value);
    }

    /**
     * Turns the Datatype's own validation failure into a Doctrine conversion failure.
     */
    private function rehydrate(string $value): Url
    {
        try {
            return Url::create($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

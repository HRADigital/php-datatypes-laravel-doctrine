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
use HraDigital\Datatypes\Web\Seo\Slug;
use Throwable;

use function is_string;

/**
 * Maps HraDigital\Datatypes\Web\Seo\Slug onto a string column.
 *
 * The column defaults to Slug::MAX_LENGTH (191) rather than 255: 191 is the widest utf8mb4
 * column MySQL 5.7 / InnoDB will accept in a single-column UNIQUE index, and a slug is
 * routinely the entity's public unique key.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class SlugType extends AbstractDatatypeType
{
    public const NAME = 'SlugType';

    /** Default column width when the mapping does not state one. */
    public const DEFAULT_LENGTH = Slug::MAX_LENGTH;

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

        if ($value instanceof Slug) {
            return (string) $value;
        }

        if (is_string($value)) {
            return (string) $this->rehydrate($value);
        }

        throw DatatypeConversionException::fromInvalidPhpValue($value, self::NAME, ['null', Slug::class, 'string']);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Slug
    {
        if ($value === null || $value instanceof Slug) {
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
    private function rehydrate(string $value): Slug
    {
        try {
            return Slug::create($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

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
use HraDigital\Datatypes\Fiscal\NifType;
use Throwable;

use function is_string;

/**
 * Maps the HraDigital\Datatypes\Fiscal\NifType enum onto a string column.
 *
 * Stored as the value the enum is backed by (`natural_person`, `legal_person`). The kind of
 * taxpayer is derivable from a Nif itself, so this column only earns its place when it has to
 * be filtered or indexed in SQL. An unknown value fails the conversion rather than rehydrating
 * to null - a case that is not in the enum is corrupt data, not an absent value.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class NifTypeType extends AbstractDatatypeType
{
    public const NAME = 'NifTypeType';

    /** Wider than the longest case today, so a case added upstream fits without a migration. */
    public const DEFAULT_LENGTH = 32;

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

        if ($value instanceof NifType) {
            return $value->value;
        }

        if (is_string($value)) {
            return $this->rehydrate($value)->value;
        }

        throw DatatypeConversionException::fromInvalidPhpValue($value, self::NAME, ['null', NifType::class, 'string']);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?NifType
    {
        if ($value === null || $value instanceof NifType) {
            return $value;
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME);
        }

        return $this->rehydrate($value);
    }

    /**
     * Turns the enum's rejection of an unknown value into a Doctrine conversion failure.
     */
    private function rehydrate(string $value): NifType
    {
        try {
            return NifType::from($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

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
use HraDigital\Datatypes\Fiscal\Nif;
use Throwable;

use function is_string;

/**
 * Maps HraDigital\Datatypes\Fiscal\Nif onto a CHAR(9) column.
 *
 * Stored as the nine normalized digits the Datatype produces - whitespace stripped - so two
 * rows holding the same NIF compare equal in SQL too. The column is fixed-width because every
 * NIF is exactly nine digits, and it is a string rather than an integer so the value is kept
 * exactly as issued.
 *
 * Not to be confused with NifTypeType, which maps the Fiscal\NifType enum - this Type follows
 * the naming rule for `Nif`, the enum's Type follows it for `NifType`.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class NifType extends AbstractDatatypeType
{
    public const NAME = 'NifType';

    /** Length of a Portuguese NIF. */
    public const DEFAULT_LENGTH = 9;

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

        if ($value instanceof Nif) {
            return (string) $value;
        }

        // A native string still goes through the Datatype, so a NIF with a wrong check digit
        // can never reach the column just because the caller skipped the value object.
        if (is_string($value)) {
            return (string) $this->rehydrate($value);
        }

        throw DatatypeConversionException::fromInvalidPhpValue($value, self::NAME, ['null', Nif::class, 'string']);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Nif
    {
        if ($value === null || $value instanceof Nif) {
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
    private function rehydrate(string $value): Nif
    {
        try {
            return Nif::create($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

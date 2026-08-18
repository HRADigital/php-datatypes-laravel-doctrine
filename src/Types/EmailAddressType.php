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
use HraDigital\Datatypes\Web\EmailAddress;
use Throwable;

use function is_string;

/**
 * Maps HraDigital\Datatypes\Web\EmailAddress onto a string column.
 *
 * The stored value is the normalized address the Datatype produces - trimmed and lowercased -
 * never the raw input, so two rows holding the same address compare equal in SQL too.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class EmailAddressType extends AbstractDatatypeType
{
    public const NAME = 'EmailAddressType';

    /** Default column width when the mapping does not state one. */
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

        if ($value instanceof EmailAddress) {
            return (string) $value;
        }

        // A native string still goes through the Datatype, so an invalid address can never
        // reach the column just because the caller skipped the value object.
        if (is_string($value)) {
            return (string) $this->rehydrate($value);
        }

        throw DatatypeConversionException::fromInvalidPhpValue(
            $value,
            self::NAME,
            ['null', EmailAddress::class, 'string']
        );
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?EmailAddress
    {
        if ($value === null || $value instanceof EmailAddress) {
            return $value;
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME);
        }

        return $this->rehydrate($value);
    }

    /**
     * Turns the Datatype's own validation failure into a Doctrine conversion failure, so a
     * malformed row surfaces as the DBAL error callers already handle.
     */
    private function rehydrate(string $value): EmailAddress
    {
        try {
            return EmailAddress::create($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, self::NAME, $e);
        }
    }
}

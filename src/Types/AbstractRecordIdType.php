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
use HraDigital\Datatypes\ValueObjects\RecordId;
use Throwable;

use function is_resource;
use function is_string;
use function stream_get_contents;

/**
 * Shared conversion for every Type that maps a HraDigital\Datatypes\ValueObjects\RecordId.
 *
 * The concrete Types differ only in the column's storage format - the canonical string or the
 * 16 raw bytes - so the conversion flow lives here and each subclass converts to and from its
 * own column. A subclass of a concrete Type overrides `datatypeClass()` to map its own RecordId
 * subclass, and then accepts only that subclass on write.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
abstract class AbstractRecordIdType extends AbstractDatatypeType
{
    /**
     * Turns the RecordId into the column's storage format.
     */
    abstract protected function toColumn(RecordId $id): string;

    /**
     * Rehydrates the RecordId from the column's storage format.
     */
    abstract protected function fromColumn(string $value): RecordId;

    /**
     * The RecordId class this Type rehydrates.
     *
     * @return class-string<RecordId>
     */
    protected function datatypeClass(): string
    {
        return RecordId::class;
    }

    /**
     * Renders a stored value for an exception message.
     */
    protected function printable(string $value): string
    {
        return $value;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        $class = $this->datatypeClass();

        if ($value === null) {
            return null;
        }

        if ($value instanceof $class) {
            return $this->toColumn($value);
        }

        // A native string still goes through the Datatype, so a non-v7 UUID can never reach the
        // column just because the caller skipped the value object.
        if (is_string($value)) {
            try {
                return $this->toColumn($class::fromString($value));
            } catch (Throwable $e) {
                throw DatatypeConversionException::fromInvalidDatabaseValue($value, static::NAME, $e);
            }
        }

        throw DatatypeConversionException::fromInvalidPhpValue($value, static::NAME, ['null', $class, 'string']);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RecordId
    {
        $class = $this->datatypeClass();

        if ($value === null || $value instanceof $class) {
            return $value;
        }

        // pdo_pgsql hands a BYTEA column back as a stream.
        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, static::NAME);
        }

        try {
            return $this->fromColumn($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($this->printable($value), static::NAME, $e);
        }
    }
}

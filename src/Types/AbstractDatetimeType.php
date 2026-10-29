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

use DateTimeImmutable as NativeDateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use HraDigital\Components\DatatypesDoctrine\Exceptions\DatatypeConversionException;
use HraDigital\Datatypes\Datetime\DatetimeContract;
use Throwable;

use function is_string;

/**
 * Shared conversion for every Type that rehydrates a HraDigital\Datatypes\Datetime\DateTime or
 * HraDigital\Datatypes\Datetime\DateTimeImmutable.
 *
 * The concrete Types differ only in which Datatypes class they rehydrate, which platform format
 * string and which column declaration they use - date, time, datetime, datetime with timezone -
 * so all of the conversion logic lives here and each subclass answers those questions.
 *
 * Neither Datatypes class is a native \DateTimeInterface - each one wraps a native
 * \DateTimeImmutable - so a value crosses into and out of the Datatype through `fromNative()`.
 * Any native \DateTimeInterface a caller assigns is still persistable.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
abstract class AbstractDatetimeType extends AbstractDatatypeType
{
    /**
     * The Datatypes class this Type rehydrates.
     *
     * @return class-string<DatetimeContract>
     */
    abstract protected function datatypeClass(): string;

    /**
     * The platform's format string for this Type's column - eg. `Y-m-d H:i:s`.
     */
    abstract protected function formatString(AbstractPlatform $platform): string;

    /**
     * Whether the unparsed fields reset to the epoch instead of inheriting "now".
     *
     * True for the partial columns (date, time): parsing `Y-m-d` without resetting leaves
     * the current wall-clock time in the hour/minute/second fields, so two reads of the very
     * same DATE column would produce two non-equal instances.
     */
    protected function resetsUnparsedFields(): bool
    {
        return false;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DatetimeContract || $value instanceof DateTimeInterface) {
            return $value->format($this->formatString($platform));
        }

        throw DatatypeConversionException::fromInvalidPhpValue(
            $value,
            static::NAME,
            ['null', DatetimeContract::class, DateTimeInterface::class]
        );
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DatetimeContract
    {
        $class = $this->datatypeClass();

        if ($value === null || $value instanceof $class) {
            return $value;
        }

        // The other Datatypes class - a DateTime handed to an immutable column, or the reverse.
        if ($value instanceof DatetimeContract) {
            return $class::fromNative($value->toNative());
        }

        if ($value instanceof DateTimeInterface) {
            return $class::fromNative($value);
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, static::NAME);
        }

        $format = $this->resetsUnparsedFields()
            ? '!' . $this->formatString($platform)
            : $this->formatString($platform);

        // createFromFormat is tried first so the platform's own format wins.
        $parsed = NativeDateTimeImmutable::createFromFormat($format, $value);

        if ($parsed instanceof NativeDateTimeImmutable) {
            return $class::fromNative($parsed);
        }

        // Falls back to free parsing - drivers do not always hand back the exact platform
        // format (fractional seconds, differing timezone offsets).
        try {
            return $class::fromString($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, static::NAME, $e);
        }
    }
}

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

use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use HraDigital\Components\DatatypesDoctrine\Exceptions\DatatypeConversionException;
use HraDigital\Datatypes\Datetime\Datetime;
use Throwable;

use function is_string;

/**
 * Shared conversion for every Type that rehydrates a HraDigital\Datatypes\Datetime\Datetime.
 *
 * The four concrete Types differ only in which platform format string and which column
 * declaration they use - date, time, datetime, datetime with timezone - so all of the
 * conversion logic lives here and each subclass answers two questions.
 *
 * Datetime extends \DateTimeImmutable, so a Datetime the ORM never touched round-trips
 * unchanged and any \DateTimeInterface a caller assigns is still persistable.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
abstract class AbstractDatetimeType extends AbstractDatatypeType
{
    /**
     * The platform's format string for this Type's column - eg. `Y-m-d H:i:s`.
     */
    abstract protected function formatString(AbstractPlatform $platform): string;

    /**
     * Whether the unparsed fields reset to the epoch instead of inheriting "now".
     *
     * True for the partial columns (date, time): parsing `Y-m-d` without resetting leaves
     * the current wall-clock time in the hour/minute/second fields, so two reads of the very
     * same DATE column would produce two non-equal Datetime instances.
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

        if ($value instanceof DateTimeInterface) {
            return $value->format($this->formatString($platform));
        }

        throw DatatypeConversionException::fromInvalidPhpValue(
            $value,
            static::NAME,
            ['null', Datetime::class, DateTimeInterface::class]
        );
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Datetime
    {
        if ($value === null || $value instanceof Datetime) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Datetime::fromString($value->format(Datetime::START_FORMAT));
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, static::NAME);
        }

        $format = $this->resetsUnparsedFields()
            ? '!' . $this->formatString($platform)
            : $this->formatString($platform);

        // createFromFormat is tried first so the platform's own format wins; it returns an
        // instance of the called class, hence a Datetime rather than a bare DateTimeImmutable.
        $parsed = Datetime::createFromFormat($format, $value);

        if ($parsed instanceof Datetime) {
            return $parsed;
        }

        // Falls back to free parsing - drivers do not always hand back the exact platform
        // format (fractional seconds, differing timezone offsets).
        try {
            return Datetime::fromString($value);
        } catch (Throwable $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, static::NAME, $e);
        }
    }
}

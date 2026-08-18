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
use JsonException;

use function is_array;
use function is_resource;
use function is_string;
use function json_decode;
use function json_encode;
use function stream_get_contents;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Shared JSON handling for the multi-part Value Objects - the ones that carry more than one
 * field and therefore have no single scalar representation.
 *
 * A JSON column keeps the Value Object mappable as ONE Entity attribute, which is what this
 * package is for. It is not the only option: both Address and Money are explicitly designed to
 * be spread over one column per field, and where the individual fields have to be queried,
 * indexed or aggregated in SQL, a Doctrine Embeddable over separate columns is the better
 * mapping. The README spells that trade-off out.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
abstract class AbstractJsonDatatypeType extends AbstractDatatypeType
{
    /** @inheritDoc */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    /**
     * Serializes the Value Object's array form.
     *
     * Slashes and unicode are left unescaped so the stored document stays readable, and
     * zero fractions are preserved so a float field does not quietly change shape on a
     * round-trip.
     *
     * @param array<string, mixed> $data
     */
    protected function encode(array $data, string $type): string
    {
        try {
            return json_encode(
                $data,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
            );
        } catch (JsonException $e) {
            throw DatatypeConversionException::fromInvalidPhpValue($data, $type, ['a JSON-encodable array']);
        }
    }

    /**
     * Reads the stored document back into an array.
     *
     * Accepts a stream because some drivers hand JSON/BLOB columns back as a resource
     * rather than a string (notably PostgreSQL through PDO).
     *
     * @return array<string, mixed>
     */
    protected function decode(mixed $value, string $type): array
    {
        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        if (!is_string($value)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, $type);
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, $type, $e);
        }

        if (!is_array($decoded)) {
            throw DatatypeConversionException::fromInvalidDatabaseValue($value, $type);
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}

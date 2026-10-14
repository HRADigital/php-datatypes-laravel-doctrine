<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

namespace HraDigital\Tests\Components\DatatypesDoctrine\Unit\Types;

use DateTimeImmutable;
use Doctrine\DBAL\Types\ConversionException;
use HraDigital\Components\DatatypesDoctrine\Types\DatetimeType;
use HraDigital\Components\DatatypesDoctrine\Types\DatetimeTzType;
use HraDigital\Components\DatatypesDoctrine\Types\DateType;
use HraDigital\Components\DatatypesDoctrine\Types\TimeType;
use HraDigital\Datatypes\Datetime\Datetime;

/**
 * The four Datatypes date/time Types share all of their conversion logic, so they are proven
 * together - what differs per Type is only the column and the format string.
 */
class DatetimeTypesTest extends AbstractTypeTestCase
{
    public function testEachNameFollowsItsClassShortName(): void
    {
        $this->assertSame('DatetimeType', DatetimeType::NAME);
        $this->assertSame('DatetimeTzType', DatetimeTzType::NAME);
        $this->assertSame('DateType', DateType::NAME);
        $this->assertSame('TimeType', TimeType::NAME);
    }

    public function testEachDeclaresItsOwnColumn(): void
    {
        $column = ['name' => 'a_column'];

        $this->assertSame('DATETIME', (new DatetimeType())->getSQLDeclaration($column, $this->platform));
        $this->assertSame('DATE', (new DateType())->getSQLDeclaration($column, $this->platform));
        $this->assertSame('TIME', (new TimeType())->getSQLDeclaration($column, $this->platform));

        // MySQL has no timezone-aware column, so DBAL falls back to a plain DATETIME here and
        // the offset is NOT stored. Documented on DatetimeTzType.
        $this->assertSame('DATETIME', (new DatetimeTzType())->getSQLDeclaration($column, $this->platform));
    }

    public function testDatetimeRoundTripsThroughBothConversions(): void
    {
        $type = new DatetimeType();
        $original = Datetime::fromString('2026-05-27 14:32:11');

        $stored = $type->convertToDatabaseValue($original, $this->platform);
        $this->assertSame('2026-05-27 14:32:11', $stored);

        $restored = $type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf(Datetime::class, $restored);
        $this->assertSame('2026-05-27 14:32:11', (string) $restored);
    }

    public function testADateColumnResetsTheTimeSoTheSameRowAlwaysRehydratesTheSame(): void
    {
        $type = new DateType();

        $stored = $type->convertToDatabaseValue(Datetime::fromString('2026-05-27 14:32:11'), $this->platform);
        $this->assertSame('2026-05-27', $stored);

        // Without the reset, the unparsed hour/minute/second would inherit the current
        // wall clock and two reads of the very same row would not be equal.
        $restored = $type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf(Datetime::class, $restored);
        $this->assertSame('2026-05-27 00:00:00', (string) $restored);
    }

    public function testATimeColumnResetsTheDate(): void
    {
        $type = new TimeType();

        $stored = $type->convertToDatabaseValue(Datetime::fromString('2026-05-27 14:32:11'), $this->platform);
        $this->assertSame('14:32:11', $stored);

        $restored = $type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf(Datetime::class, $restored);
        $this->assertSame('1970-01-01 14:32:11', (string) $restored);
    }

    public function testAnyDateTimeInterfaceIsPersistableAndComesBackAsADatatype(): void
    {
        $type = new DatetimeType();

        $stored = $type->convertToDatabaseValue(new DateTimeImmutable('2026-05-27 14:32:11'), $this->platform);
        $this->assertSame('2026-05-27 14:32:11', $stored);

        $restored = $type->convertToPHPValue(new DateTimeImmutable('2026-05-27 14:32:11'), $this->platform);
        $this->assertInstanceOf(Datetime::class, $restored);
        $this->assertSame('2026-05-27 14:32:11', (string) $restored);
    }

    public function testFallsBackToFreeParsingWhenTheDriverDoesNotUseThePlatformFormat(): void
    {
        // Drivers hand back fractional seconds and differing offsets; the platform format
        // does not match those, and the value still has to load.
        $restored = (new DatetimeType())->convertToPHPValue('2026-05-27T14:32:11+00:00', $this->platform);

        $this->assertInstanceOf(Datetime::class, $restored);
        $this->assertSame('2026-05-27 14:32:11', (string) $restored);
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $type = new DatetimeType();

        $this->assertNull($type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($type->convertToPHPValue(null, $this->platform));
    }

    public function testADatatypeAlreadyRehydratedPassesThroughUntouched(): void
    {
        $datetime = Datetime::fromString('2026-05-27 14:32:11');

        $this->assertSame($datetime, (new DatetimeType())->convertToPHPValue($datetime, $this->platform));
    }

    public function testRejectsANonStringColumnValue(): void
    {
        $this->expectException(ConversionException::class);

        (new DatetimeType())->convertToPHPValue(20260527, $this->platform);
    }

    public function testRejectsAValueItCannotPersist(): void
    {
        $this->expectException(ConversionException::class);

        (new DatetimeType())->convertToDatabaseValue('2026-05-27', $this->platform);
    }

    public function testAMalformedRowSurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        (new DatetimeType())->convertToPHPValue('not a datetime', $this->platform);
    }
}

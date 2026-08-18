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

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\ConversionException;
use HraDigital\Components\DatatypesDoctrine\Types\NativeDateIntervalType;
use HraDigital\Components\DatatypesDoctrine\Types\NativeDateTimeImmutableType;
use HraDigital\Components\DatatypesDoctrine\Types\NativeDateTimeType;
use HraDigital\Components\DatatypesDoctrine\Types\NativeDateTimeZoneType;

/**
 * The Types covering the native PHP classes, so an Entity holding a plain `?DateTime` maps with
 * the same naming convention as one holding a Datatype.
 */
class NativeTypesTest extends AbstractTypeTestCase
{
    public function testEachNameCarriesTheNativePrefix(): void
    {
        // The prefix is load-bearing: \DateInterval and \DateTimeZone share their short name
        // with a Datatypes class outright, and \DateTime differs from Datetime by one capital.
        $this->assertSame('NativeDateTimeType', NativeDateTimeType::NAME);
        $this->assertSame('NativeDateTimeImmutableType', NativeDateTimeImmutableType::NAME);
        $this->assertSame('NativeDateIntervalType', NativeDateIntervalType::NAME);
        $this->assertSame('NativeDateTimeZoneType', NativeDateTimeZoneType::NAME);
    }

    public function testDateTimeRoundTripsThroughBothConversions(): void
    {
        $type = new NativeDateTimeType();

        $this->assertSame('DATETIME', $type->getSQLDeclaration(['name' => 'date_of_birth'], $this->platform));

        $stored = $type->convertToDatabaseValue(new DateTime('1984-02-29 08:15:00'), $this->platform);
        $this->assertSame('1984-02-29 08:15:00', $stored);

        $restored = $type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf(DateTime::class, $restored);
        $this->assertSame('1984-02-29 08:15:00', $restored->format('Y-m-d H:i:s'));
    }

    public function testDateTimeImmutableRoundTripsThroughBothConversions(): void
    {
        $type = new NativeDateTimeImmutableType();

        $stored = $type->convertToDatabaseValue(new DateTimeImmutable('2026-05-27 14:32:11'), $this->platform);
        $this->assertSame('2026-05-27 14:32:11', $stored);

        $restored = $type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf(DateTimeImmutable::class, $restored);
        $this->assertSame('2026-05-27 14:32:11', $restored->format('Y-m-d H:i:s'));
    }

    public function testDateIntervalCarriesItsSignAcrossTheRoundTrip(): void
    {
        $type = new NativeDateIntervalType();
        $interval = new DateInterval('P2DT4H');
        $interval->invert = 1;

        $stored = $type->convertToDatabaseValue($interval, $this->platform);
        $this->assertSame('-P0Y0M2DT4H0M0S', $stored);

        $restored = $type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf(DateInterval::class, $restored);
        $this->assertSame(2, $restored->d);
        $this->assertSame(4, $restored->h);
        $this->assertSame(1, $restored->invert);
    }

    public function testDateTimeZoneRoundTripsThroughBothConversions(): void
    {
        $type = new NativeDateTimeZoneType();

        $stored = $type->convertToDatabaseValue(new DateTimeZone('Atlantic/Azores'), $this->platform);
        $this->assertSame('Atlantic/Azores', $stored);

        $restored = $type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf(DateTimeZone::class, $restored);
        $this->assertSame('Atlantic/Azores', $restored->getName());
    }

    public function testNullPassesThroughUntouchedOnEveryNativeType(): void
    {
        foreach (
            [
                new NativeDateTimeType(),
                new NativeDateTimeImmutableType(),
                new NativeDateIntervalType(),
                new NativeDateTimeZoneType(),
            ] as $type
        ) {
            $this->assertNull($type->convertToDatabaseValue(null, $this->platform));
            $this->assertNull($type->convertToPHPValue(null, $this->platform));
        }
    }

    public function testEachRejectsAValueItCannotPersist(): void
    {
        $this->expectException(ConversionException::class);

        (new NativeDateIntervalType())->convertToDatabaseValue('P1D', $this->platform);
    }

    public function testAMalformedRowSurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        (new NativeDateTimeZoneType())->convertToPHPValue('Middle/Earth', $this->platform);
    }
}

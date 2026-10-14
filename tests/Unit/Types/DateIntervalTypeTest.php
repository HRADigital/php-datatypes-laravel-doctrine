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

use Doctrine\DBAL\Types\ConversionException;
use HraDigital\Components\DatatypesDoctrine\Types\DateIntervalType;
use HraDigital\Datatypes\Datetime\DateInterval;
use HraDigital\Datatypes\Scalar\Str;

class DateIntervalTypeTest extends AbstractTypeTestCase
{
    private DateIntervalType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new DateIntervalType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('DateIntervalType', DateIntervalType::NAME);
    }

    public function testDeclaresAStringColumn(): void
    {
        $this->assertSame(
            'VARCHAR(64)',
            $this->type->getSQLDeclaration(['name' => 'duration'], $this->platform)
        );
    }

    public function testStoresAnIsoDuration(): void
    {
        $this->assertSame(
            'P0Y0M3DT0H0M0S',
            $this->type->convertToDatabaseValue(DateInterval::fromDays(3), $this->platform)
        );
    }

    public function testCarriesTheSignExplicitlyBecauseIsoDurationsHaveNoDirection(): void
    {
        // Persisting the duration alone would silently turn every negative interval positive
        // on the next read - hence the `-` prefix, which is not part of ISO-8601.
        $stored = $this->type->convertToDatabaseValue(DateInterval::fromDays(-3), $this->platform);

        $this->assertSame('-P0Y0M3DT0H0M0S', $stored);

        $restored = $this->type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf(DateInterval::class, $restored);
        $this->assertTrue($restored->isNegative());
        $this->assertSame(-3, $restored->getDays());
    }

    public function testRoundTripsEveryCalendarField(): void
    {
        $original = DateInterval::fromDuration(Str::create('P1Y2M3DT4H5M6S'));

        $restored = $this->type->convertToPHPValue(
            $this->type->convertToDatabaseValue($original, $this->platform),
            $this->platform
        );

        $this->assertInstanceOf(DateInterval::class, $restored);
        $this->assertSame(1, $restored->getYears());
        $this->assertSame(2, $restored->getMonths());
        $this->assertSame(3, $restored->getDays());
        $this->assertSame(4, $restored->getHours());
        $this->assertSame(5, $restored->getMinutes());
        $this->assertSame(6, $restored->getSeconds());
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testRejectsAValueItCannotPersist(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('P1D', $this->platform);
    }

    public function testADatatypeAlreadyRehydratedPassesThroughUntouched(): void
    {
        $interval = DateInterval::fromDays(3);

        $this->assertSame($interval, $this->type->convertToPHPValue($interval, $this->platform));
    }

    public function testRejectsANonStringColumnValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue(3, $this->platform);
    }

    public function testAMalformedRowSurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('not a duration', $this->platform);
    }
}

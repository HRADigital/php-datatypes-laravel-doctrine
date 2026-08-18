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

use DateTimeZone as NativeDateTimeZone;
use Doctrine\DBAL\Types\ConversionException;
use HraDigital\Components\DatatypesDoctrine\Types\DateTimeZoneType;
use HraDigital\Datatypes\Datetime\DateTimeZone;

class DateTimeZoneTypeTest extends AbstractTypeTestCase
{
    private DateTimeZoneType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new DateTimeZoneType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('DateTimeZoneType', DateTimeZoneType::NAME);
    }

    public function testDeclaresAStringColumn(): void
    {
        $this->assertSame(
            'VARCHAR(64)',
            $this->type->getSQLDeclaration(['name' => 'timezone'], $this->platform)
        );
    }

    public function testStoresTheIanaIdentifier(): void
    {
        // Never a fixed offset: an offset cannot express daylight saving, so a stored
        // `+01:00` would be wrong for half the year.
        $this->assertSame(
            'Europe/Lisbon',
            $this->type->convertToDatabaseValue(new DateTimeZone('Europe/Lisbon'), $this->platform)
        );
    }

    public function testRoundTripsThroughBothConversions(): void
    {
        $restored = $this->type->convertToPHPValue('Europe/Lisbon', $this->platform);

        $this->assertInstanceOf(DateTimeZone::class, $restored);
        $this->assertSame('Europe/Lisbon', $restored->getName());
    }

    public function testAcceptsTheNativeClassBecauseTheDatatypeExtendsIt(): void
    {
        $stored = $this->type->convertToDatabaseValue(new NativeDateTimeZone('UTC'), $this->platform);

        $this->assertSame('UTC', $stored);
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testAnUnknownIdentifierNeverReachesTheColumn(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('Middle/Earth', $this->platform);
    }

    public function testAMalformedRowSurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('Middle/Earth', $this->platform);
    }
}

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
use HraDigital\Components\DatatypesDoctrine\Types\NifTypeType;
use HraDigital\Datatypes\Fiscal\NifType;

class NifTypeTypeTest extends AbstractTypeTestCase
{
    private NifTypeType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new NifTypeType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('NifTypeType', NifTypeType::NAME);
    }

    public function testDeclaresAStringColumn(): void
    {
        $this->assertSame(
            'VARCHAR(32)',
            $this->type->getSQLDeclaration(['name' => 'nif_type'], $this->platform)
        );
    }

    public function testStoresTheBackingValue(): void
    {
        $this->assertSame(
            'legal_person',
            $this->type->convertToDatabaseValue(NifType::LEGAL_PERSON, $this->platform)
        );
    }

    public function testAcceptsTheBackingValueAsANativeString(): void
    {
        $this->assertSame(
            'natural_person',
            $this->type->convertToDatabaseValue('natural_person', $this->platform)
        );
    }

    public function testRoundTripsThroughBothConversions(): void
    {
        $stored = $this->type->convertToDatabaseValue(NifType::NATURAL_PERSON, $this->platform);

        $this->assertSame(NifType::NATURAL_PERSON, $this->type->convertToPHPValue($stored, $this->platform));
    }

    public function testACaseAlreadyRehydratedPassesThroughUntouched(): void
    {
        $this->assertSame(
            NifType::LEGAL_PERSON,
            $this->type->convertToPHPValue(NifType::LEGAL_PERSON, $this->platform)
        );
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testAnUnknownValueFailsRatherThanRehydratingToNull(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('alien', $this->platform);
    }

    public function testAnUnknownValueNeverReachesTheColumn(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('alien', $this->platform);
    }

    public function testRejectsAValueItCannotPersist(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(1, $this->platform);
    }

    public function testRejectsANonStringColumnValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue(1, $this->platform);
    }
}

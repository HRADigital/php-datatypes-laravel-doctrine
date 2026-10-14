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
use HraDigital\Components\DatatypesDoctrine\Types\NifType;
use HraDigital\Datatypes\Fiscal\Nif;
use HraDigital\Datatypes\Fiscal\NifType as NifKind;

class NifTypeTest extends AbstractTypeTestCase
{
    private NifType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new NifType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('NifType', NifType::NAME);
    }

    public function testDeclaresAFixedWidthColumnBecauseEveryNifIsNineDigits(): void
    {
        $this->assertSame(
            'CHAR(9)',
            $this->type->getSQLDeclaration(['name' => 'nif'], $this->platform)
        );
    }

    public function testStoresTheNormalizedDigitsRatherThanTheRawInput(): void
    {
        $this->assertSame('123456789', $this->type->convertToDatabaseValue('123 456 789', $this->platform));
    }

    public function testConvertsANifToItsPrimitive(): void
    {
        $this->assertSame(
            '500000000',
            $this->type->convertToDatabaseValue(Nif::create('500000000'), $this->platform)
        );
    }

    public function testRoundTripsThroughBothConversions(): void
    {
        $original = Nif::create('123456789');

        $restored = $this->type->convertToPHPValue(
            $this->type->convertToDatabaseValue($original, $this->platform),
            $this->platform
        );

        $this->assertInstanceOf(Nif::class, $restored);
        $this->assertTrue($original->equals($restored));
        $this->assertSame(NifKind::NATURAL_PERSON, $restored->getType());
    }

    public function testANifAlreadyRehydratedPassesThroughUntouched(): void
    {
        $nif = Nif::create('123456789');

        $this->assertSame($nif, $this->type->convertToPHPValue($nif, $this->platform));
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testANifWithAWrongCheckDigitNeverReachesTheColumn(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('123456780', $this->platform);
    }

    public function testAMalformedRowSurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('12345', $this->platform);
    }

    public function testRejectsAValueItCannotPersist(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(123456789, $this->platform);
    }

    public function testRejectsANonStringColumnValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue(123456789, $this->platform);
    }
}

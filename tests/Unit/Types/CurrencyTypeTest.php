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
use HraDigital\Components\DatatypesDoctrine\Types\CurrencyType;
use HraDigital\Datatypes\Financial\Currency;

class CurrencyTypeTest extends AbstractTypeTestCase
{
    private CurrencyType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new CurrencyType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('CurrencyType', CurrencyType::NAME);
    }

    public function testDeclaresAFixedWidthColumnBecauseEveryCodeIsThreeCharacters(): void
    {
        $this->assertSame(
            'CHAR(3)',
            $this->type->getSQLDeclaration(['name' => 'currency'], $this->platform)
        );
    }

    public function testStoresTheIso4217Code(): void
    {
        $this->assertSame('EUR', $this->type->convertToDatabaseValue(Currency::EUR, $this->platform));
    }

    public function testRoundTripsThroughBothConversions(): void
    {
        $stored = $this->type->convertToDatabaseValue(Currency::JPY, $this->platform);
        $restored = $this->type->convertToPHPValue($stored, $this->platform);

        $this->assertSame(Currency::JPY, $restored);
    }

    public function testAcceptsTheCodeAsANativeString(): void
    {
        $this->assertSame('USD', $this->type->convertToDatabaseValue('USD', $this->platform));
    }

    public function testACaseAlreadyRehydratedPassesThroughUntouched(): void
    {
        $this->assertSame(Currency::EUR, $this->type->convertToPHPValue(Currency::EUR, $this->platform));
    }

    public function testRejectsAValueItCannotPersist(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(978, $this->platform);
    }

    public function testRejectsANonStringColumnValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue(978, $this->platform);
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testAnUnknownCodeFailsRatherThanRehydratingToNull(): void
    {
        // A currency that is not in the enum is corrupt data, not an absent value.
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('XXX', $this->platform);
    }

    public function testAnUnknownCodeNeverReachesTheColumn(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('XXX', $this->platform);
    }
}

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
use HraDigital\Components\DatatypesDoctrine\Types\MoneyType;
use HraDigital\Datatypes\Financial\Currency;
use HraDigital\Datatypes\Financial\Money;

class MoneyTypeTest extends AbstractTypeTestCase
{
    private MoneyType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new MoneyType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('MoneyType', MoneyType::NAME);
    }

    public function testDeclaresAJsonColumn(): void
    {
        // Asserted against the platform rather than a literal: the same MySQL platform emits
        // LONGTEXT on DBAL 3 and JSON on DBAL 4, and this package supports both.
        $column = ['name' => 'total'];

        $this->assertSame(
            $this->platform->getJsonTypeDeclarationSQL($column),
            $this->type->getSQLDeclaration($column, $this->platform)
        );
    }

    public function testStoresMinorUnitsAsAnIntegerNeverAsAFloat(): void
    {
        $stored = $this->type->convertToDatabaseValue(Money::fromDecimal('12.34', Currency::EUR), $this->platform);

        $this->assertSame('{"amount":1234,"currency":"EUR"}', $stored);
    }

    public function testRoundTripsThroughBothConversions(): void
    {
        $original = Money::fromDecimal('-1234567.89', Currency::EUR);

        $restored = $this->type->convertToPHPValue(
            $this->type->convertToDatabaseValue($original, $this->platform),
            $this->platform
        );

        $this->assertInstanceOf(Money::class, $restored);
        $this->assertTrue($original->equals($restored));
        $this->assertSame('-1234567.89', $restored->toDecimal());
    }

    public function testRoundTripsACurrencyWithNoMinorUnits(): void
    {
        $original = Money::fromDecimal('1500', Currency::JPY);

        $restored = $this->type->convertToPHPValue(
            $this->type->convertToDatabaseValue($original, $this->platform),
            $this->platform
        );

        $this->assertInstanceOf(Money::class, $restored);
        $this->assertSame('1500', $restored->toDecimal());
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testRejectsAValueItCannotPersist(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(['amount' => 1, 'currency' => 'EUR'], $this->platform);
    }

    public function testAMalformedDocumentSurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('{not json', $this->platform);
    }

    public function testADocumentWithAnUnknownCurrencySurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('{"amount":100,"currency":"XXX"}', $this->platform);
    }
}

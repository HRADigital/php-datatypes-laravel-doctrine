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
use HraDigital\Components\DatatypesDoctrine\Types\StrType;
use HraDigital\Datatypes\Scalar\Str;

class StrTypeTest extends AbstractTypeTestCase
{
    private StrType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new StrType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('StrType', StrType::NAME);
        $this->assertSame('StrType', $this->type->getName());
    }

    public function testDeclaresAStringColumn(): void
    {
        $this->assertSame(
            'VARCHAR(64)',
            $this->type->getSQLDeclaration(['name' => 'a_column', 'length' => 64], $this->platform)
        );
    }

    public function testDeclaresADefaultWidthWhenTheMappingStatesNone(): void
    {
        $this->assertSame(
            'VARCHAR(255)',
            $this->type->getSQLDeclaration(['name' => 'a_column'], $this->platform)
        );
    }

    public function testConvertsAStrToItsPrimitive(): void
    {
        $this->assertSame(
            'a value',
            $this->type->convertToDatabaseValue(Str::create('a value'), $this->platform)
        );
    }

    public function testAcceptsANativeStringSoEntitiesCanBeHydratedFromPrimitives(): void
    {
        $this->assertSame('a value', $this->type->convertToDatabaseValue('a value', $this->platform));
    }

    public function testRehydratesAStrFromTheColumn(): void
    {
        $value = $this->type->convertToPHPValue('a value', $this->platform);

        $this->assertInstanceOf(Str::class, $value);
        $this->assertSame('a value', (string) $value);
    }

    public function testRoundTripsThroughBothConversions(): void
    {
        $original = Str::create('Round trip - áéíóú');

        $stored = $this->type->convertToDatabaseValue($original, $this->platform);
        $restored = $this->type->convertToPHPValue($stored, $this->platform);

        $this->assertInstanceOf(Str::class, $restored);
        $this->assertSame((string) $original, (string) $restored);
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testRejectsAValueItCannotPersist(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(42, $this->platform);
    }

    public function testRejectsANonStringColumnValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue(42, $this->platform);
    }
}

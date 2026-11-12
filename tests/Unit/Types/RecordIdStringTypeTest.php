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
use HraDigital\Components\DatatypesDoctrine\Types\RecordIdStringType;
use HraDigital\Datatypes\ValueObjects\RecordId;

use function strtoupper;

class RecordIdStringTypeTest extends AbstractTypeTestCase
{
    private const V7 = '0190d6f3-5c4a-7b2e-9f1d-3a4b5c6d7e8f';

    private const V4 = '550e8400-e29b-41d4-a716-446655440000';

    private RecordIdStringType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new RecordIdStringType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('RecordIdStringType', RecordIdStringType::NAME);
    }

    public function testDeclaresTheGuidColumn(): void
    {
        $this->assertSame('CHAR(36)', $this->type->getSQLDeclaration(['name' => 'id'], $this->platform));
    }

    public function testConvertsARecordIdToItsString(): void
    {
        $this->assertSame(
            self::V7,
            $this->type->convertToDatabaseValue(RecordId::fromString(self::V7), $this->platform)
        );
    }

    public function testStoresAnUpperCaseInputInLowerCase(): void
    {
        $this->assertSame(self::V7, $this->type->convertToDatabaseValue(strtoupper(self::V7), $this->platform));
    }

    public function testRoundTripsThroughBothConversions(): void
    {
        $original = RecordId::generate();

        $restored = $this->type->convertToPHPValue(
            $this->type->convertToDatabaseValue($original, $this->platform),
            $this->platform
        );

        $this->assertInstanceOf(RecordId::class, $restored);
        $this->assertTrue($original->equals($restored));
    }

    public function testARecordIdAlreadyRehydratedPassesThroughUntouched(): void
    {
        $id = RecordId::generate();

        $this->assertSame($id, $this->type->convertToPHPValue($id, $this->platform));
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testAV4UuidNeverReachesTheColumn(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(self::V4, $this->platform);
    }

    public function testAMalformedRowSurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('not-a-uuid', $this->platform);
    }

    public function testRejectsANonStringColumnValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue(123, $this->platform);
    }

    public function testRejectsAValueItCannotPersist(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(123, $this->platform);
    }
}

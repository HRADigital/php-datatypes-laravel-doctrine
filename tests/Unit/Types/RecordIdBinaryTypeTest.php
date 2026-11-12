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

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\ConversionException;
use HraDigital\Components\DatatypesDoctrine\Types\RecordIdBinaryType;
use HraDigital\Datatypes\ValueObjects\RecordId;
use HraDigital\Tests\Components\DatatypesDoctrine\Unit\Types\Fixtures\OrderId;
use HraDigital\Tests\Components\DatatypesDoctrine\Unit\Types\Fixtures\OrderIdBinaryType;
use HraDigital\Tests\Components\DatatypesDoctrine\Unit\Types\Fixtures\UserId;

use function bin2hex;
use function fopen;
use function fwrite;
use function hex2bin;
use function rewind;
use function str_repeat;

class RecordIdBinaryTypeTest extends AbstractTypeTestCase
{
    private const V7 = '0190d6f3-5c4a-7b2e-9f1d-3a4b5c6d7e8f';

    private const V7_HEX = '0190d6f35c4a7b2e9f1d3a4b5c6d7e8f';

    private const V4 = '550e8400-e29b-41d4-a716-446655440000';

    private const V4_HEX = '550e8400e29b41d4a716446655440000';

    private RecordIdBinaryType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new RecordIdBinaryType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('RecordIdBinaryType', RecordIdBinaryType::NAME);
    }

    public function testDeclaresAFixedSixteenByteColumn(): void
    {
        $this->assertSame('BINARY(16)', $this->type->getSQLDeclaration(['name' => 'id'], $this->platform));
    }

    public function testIgnoresALengthInTheMapping(): void
    {
        $this->assertSame(
            'BINARY(16)',
            $this->type->getSQLDeclaration(['name' => 'id', 'length' => 36], $this->platform)
        );
    }

    public function testBindsAsBinary(): void
    {
        $this->assertSame(ParameterType::BINARY, $this->type->getBindingType());
    }

    public function testStoresTheRawBytes(): void
    {
        $id = RecordId::fromString(self::V7);

        $this->assertSame($id->toBytes(), $this->type->convertToDatabaseValue($id, $this->platform));
    }

    public function testStoresACanonicalStringAsBytes(): void
    {
        $this->assertSame(hex2bin(self::V7_HEX), $this->type->convertToDatabaseValue(self::V7, $this->platform));
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

    public function testReadsAStream(): void
    {
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, (string) hex2bin(self::V7_HEX));
        rewind($stream);

        $restored = $this->type->convertToPHPValue($stream, $this->platform);

        $this->assertInstanceOf(RecordId::class, $restored);
        $this->assertSame(self::V7, (string) $restored);
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testRejectsFifteenBytesWithTheHexInTheMessage(): void
    {
        $bytes = str_repeat("\x01", 15);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage(bin2hex($bytes));

        $this->type->convertToPHPValue($bytes, $this->platform);
    }

    public function testRejectsV4Bytes(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue(hex2bin(self::V4_HEX), $this->platform);
    }

    public function testAV4UuidNeverReachesTheColumn(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(self::V4, $this->platform);
    }

    public function testRejectsANonStringColumnValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue(123, $this->platform);
    }

    public function testASubclassReadsItsOwnRecordId(): void
    {
        $restored = (new OrderIdBinaryType())->convertToPHPValue(hex2bin(self::V7_HEX), $this->platform);

        $this->assertInstanceOf(OrderId::class, $restored);
    }

    public function testASubclassRejectsAnotherRecordId(): void
    {
        $this->expectException(ConversionException::class);

        (new OrderIdBinaryType())->convertToDatabaseValue(UserId::fromString(self::V7), $this->platform);
    }
}

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
use HraDigital\Components\DatatypesDoctrine\Types\AddressType;
use HraDigital\Datatypes\ValueObjects\Address;

use function fopen;
use function fwrite;
use function rewind;

class AddressTypeTest extends AbstractTypeTestCase
{
    private AddressType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new AddressType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('AddressType', AddressType::NAME);
    }

    public function testDeclaresAJsonColumn(): void
    {
        // Asserted against the platform rather than a literal: the same MySQL platform emits
        // LONGTEXT on DBAL 3 and JSON on DBAL 4, and this package supports both. What has to
        // hold is that the Type asks the platform for a JSON column, whatever that renders as.
        $column = ['name' => 'address'];

        $this->assertSame(
            $this->platform->getJsonTypeDeclarationSQL($column),
            $this->type->getSQLDeclaration($column, $this->platform)
        );
    }

    public function testStoresTheValueObjectsOwnArrayShape(): void
    {
        $stored = $this->type->convertToDatabaseValue(
            new Address('Rua das Flores 12', '1000-001', 'Lisboa', 'PT'),
            $this->platform
        );

        $this->assertSame(
            '{"street":"Rua das Flores 12","postal_code":"1000-001","city":"Lisboa","country":"PT"}',
            $stored
        );
    }

    public function testLeavesUnicodeUnescapedSoTheStoredDocumentStaysReadable(): void
    {
        $stored = $this->type->convertToDatabaseValue(
            new Address('Praça da Alegria', '1250-001', 'Lisboa', 'PT'),
            $this->platform
        );

        $this->assertStringContainsString('Praça da Alegria', (string) $stored);
    }

    public function testRoundTripsThroughBothConversions(): void
    {
        $original = new Address('Rua das Flores 12', '1000-001', 'Lisboa', 'PT');

        $restored = $this->type->convertToPHPValue(
            $this->type->convertToDatabaseValue($original, $this->platform),
            $this->platform
        );

        $this->assertInstanceOf(Address::class, $restored);
        $this->assertTrue($original->equals($restored));
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testRejectsAValueItCannotPersist(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('Rua das Flores 12', $this->platform);
    }

    public function testAMalformedDocumentSurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('{not json', $this->platform);
    }

    public function testAJsonScalarIsNotAnAddress(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('"just a string"', $this->platform);
    }

    public function testReadsADocumentHandedBackAsAStream(): void
    {
        // Some drivers (notably PostgreSQL through PDO) return JSON columns as a resource.
        $stream = fopen('php://memory', 'r+');
        $this->assertIsResource($stream);
        fwrite($stream, '{"street":"Rua das Flores 12","postal_code":"1000-001","city":"Lisboa","country":"PT"}');
        rewind($stream);

        $restored = $this->type->convertToPHPValue($stream, $this->platform);

        $this->assertInstanceOf(Address::class, $restored);
        $this->assertSame('Lisboa', $restored->getCity());
    }

    public function testAnAddressAlreadyRehydratedPassesThroughUntouched(): void
    {
        $address = new Address('Rua das Flores 12', '1000-001', 'Lisboa', 'PT');

        $this->assertSame($address, $this->type->convertToPHPValue($address, $this->platform));
    }

    public function testRejectsAColumnValueThatIsNeitherAStringNorAStream(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue(42, $this->platform);
    }
}

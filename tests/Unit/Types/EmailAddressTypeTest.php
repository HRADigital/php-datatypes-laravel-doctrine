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
use HraDigital\Components\DatatypesDoctrine\Types\EmailAddressType;
use HraDigital\Datatypes\Web\EmailAddress;

class EmailAddressTypeTest extends AbstractTypeTestCase
{
    private EmailAddressType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new EmailAddressType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('EmailAddressType', EmailAddressType::NAME);
    }

    public function testDeclaresAStringColumnWithADefaultWidth(): void
    {
        $this->assertSame(
            'VARCHAR(255)',
            $this->type->getSQLDeclaration(['name' => 'email'], $this->platform)
        );
    }

    public function testTheMappingCanStillOverrideTheWidth(): void
    {
        $this->assertSame(
            'VARCHAR(120)',
            $this->type->getSQLDeclaration(['name' => 'email', 'length' => 120], $this->platform)
        );
    }

    public function testStoresTheNormalizedAddressRatherThanTheRawInput(): void
    {
        // Two rows holding the same address have to compare equal in SQL too, so what lands in
        // the column is the case-normalized address the Datatype produces, not what was typed.
        $this->assertSame(
            'someone@example.com',
            $this->type->convertToDatabaseValue('SomeOne@Example.COM', $this->platform)
        );
    }

    public function testSurroundingWhitespaceIsRejectedRatherThanTrimmedAway(): void
    {
        // Upstream behaviour, asserted here so a change to it is caught by this suite:
        // EmailAddress lowercases and trims into its own working copy, but validates the RAW
        // argument - so a padded address fails validation instead of being trimmed clean.
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('  someone@example.com  ', $this->platform);
    }

    public function testConvertsAnEmailAddressToItsPrimitive(): void
    {
        $this->assertSame(
            'someone@example.com',
            $this->type->convertToDatabaseValue(EmailAddress::create('someone@example.com'), $this->platform)
        );
    }

    public function testRehydratesAnEmailAddressFromTheColumn(): void
    {
        $value = $this->type->convertToPHPValue('someone@example.com', $this->platform);

        $this->assertInstanceOf(EmailAddress::class, $value);
        $this->assertSame('someone', (string) $value->getUsername());
        $this->assertSame('example', (string) $value->getDomain());
        $this->assertSame('com', (string) $value->getTld());
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testAnInvalidAddressNeverReachesTheColumn(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('not-an-address', $this->platform);
    }

    public function testAMalformedRowSurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('not-an-address', $this->platform);
    }

    public function testRejectsAValueItCannotPersist(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(42, $this->platform);
    }
}

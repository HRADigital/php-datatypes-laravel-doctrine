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
use HraDigital\Components\DatatypesDoctrine\Types\UrlType;
use HraDigital\Datatypes\Web\Url;

class UrlTypeTest extends AbstractTypeTestCase
{
    private UrlType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new UrlType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('UrlType', UrlType::NAME);
    }

    public function testDeclaresAStringColumnWideEnoughForAUrl(): void
    {
        $this->assertSame(
            'VARCHAR(2048)',
            $this->type->getSQLDeclaration(['name' => 'url'], $this->platform)
        );
    }

    public function testStoresTheNormalizedUrl(): void
    {
        // Scheme and host are case-insensitive per RFC 3986 and get lowercased; the path is
        // case-SENSITIVE and has to survive untouched.
        $this->assertSame(
            'https://example.com/Path?q=1',
            $this->type->convertToDatabaseValue('HTTPS://Example.COM/Path?q=1', $this->platform)
        );
    }

    public function testRoundTripsThroughBothConversions(): void
    {
        $original = Url::create('https://example.com/path?q=1');

        $stored = $this->type->convertToDatabaseValue($original, $this->platform);
        $restored = $this->type->convertToPHPValue($stored, $this->platform);

        $this->assertInstanceOf(Url::class, $restored);
        $this->assertTrue($original->equals($restored));
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testARelativeUrlNeverReachesTheColumn(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('/just/a/path', $this->platform);
    }

    public function testAMalformedRowSurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('not a url', $this->platform);
    }

    public function testAUrlAlreadyRehydratedPassesThroughUntouched(): void
    {
        $url = Url::create('https://example.com/');

        $this->assertSame($url, $this->type->convertToPHPValue($url, $this->platform));
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

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
use HraDigital\Components\DatatypesDoctrine\Types\SlugType;
use HraDigital\Datatypes\Web\Seo\Slug;

class SlugTypeTest extends AbstractTypeTestCase
{
    private SlugType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new SlugType();
    }

    public function testNameFollowsTheClassShortName(): void
    {
        $this->assertSame('SlugType', SlugType::NAME);
    }

    public function testDefaultsToTheWidestIndexableUtf8mb4Column(): void
    {
        // 191, not 255: a slug is routinely the entity's public unique key, and 191 is the
        // widest utf8mb4 column MySQL 5.7 / InnoDB accepts in a single-column UNIQUE index.
        $this->assertSame(
            'VARCHAR(191)',
            $this->type->getSQLDeclaration(['name' => 'slug'], $this->platform)
        );
        $this->assertSame(Slug::MAX_LENGTH, SlugType::DEFAULT_LENGTH);
    }

    public function testRoundTripsThroughBothConversions(): void
    {
        $original = Slug::fromTitle('Olá Mundo - a First Post!');

        $stored = $this->type->convertToDatabaseValue($original, $this->platform);
        $this->assertSame('ola-mundo-a-first-post', $stored);

        $restored = $this->type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf(Slug::class, $restored);
        $this->assertTrue($original->equals($restored));
    }

    public function testNullPassesThroughUntouchedInBothDirections(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testAValueViolatingTheSlugFormatNeverReachesTheColumn(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('Not A Slug', $this->platform);
    }

    public function testAMalformedRowSurfacesAsAConversionFailure(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('Not A Slug', $this->platform);
    }
}

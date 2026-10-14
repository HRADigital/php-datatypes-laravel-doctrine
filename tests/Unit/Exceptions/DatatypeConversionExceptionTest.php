<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

namespace HraDigital\Tests\Components\DatatypesDoctrine\Unit\Exceptions;

use Doctrine\DBAL\Types\ConversionException;
use HraDigital\Components\DatatypesDoctrine\Exceptions\DatatypeConversionException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class DatatypeConversionExceptionTest extends TestCase
{
    public function testIsADoctrineConversionExceptionSoExistingCatchBlocksStillApply(): void
    {
        $this->assertInstanceOf(
            ConversionException::class,
            DatatypeConversionException::fromInvalidDatabaseValue('x', 'StrType')
        );
    }

    public function testAnInvalidPhpValueNamesTheValueTheTypeAndWhatWasExpected(): void
    {
        $exception = DatatypeConversionException::fromInvalidPhpValue(42, 'MoneyType', ['null', 'Money']);

        $this->assertSame(
            'Could not convert PHP value 42 of type "int" to a database value for Doctrine type "MoneyType". '
            . 'Expected one of: null, Money.',
            $exception->getMessage()
        );
    }

    public function testAnObjectIsDescribedByItsClassRatherThanDumped(): void
    {
        $exception = DatatypeConversionException::fromInvalidPhpValue(new stdClass(), 'StrType', ['null']);

        $this->assertStringContainsString('PHP value of class "stdClass"', $exception->getMessage());
    }

    public function testAnArrayIsDescribedByItsTypeRatherThanDumped(): void
    {
        $exception = DatatypeConversionException::fromInvalidDatabaseValue(['a' => 1], 'AddressType');

        $this->assertSame(
            'Could not convert database value of class "array" to the PHP value of Doctrine type "AddressType".',
            $exception->getMessage()
        );
    }

    public function testNullIsDescribedLiterally(): void
    {
        $exception = DatatypeConversionException::fromInvalidPhpValue(null, 'StrType', ['string']);

        $this->assertStringContainsString('PHP value NULL of type "null"', $exception->getMessage());
    }

    public function testAnInvalidDatabaseValueKeepsTheUnderlyingCause(): void
    {
        $cause = new RuntimeException('upstream validation failed');

        $exception = DatatypeConversionException::fromInvalidDatabaseValue('bad', 'SlugType', $cause);

        $this->assertSame($cause, $exception->getPrevious());
        $this->assertStringContainsString("database value 'bad'", $exception->getMessage());
    }
}

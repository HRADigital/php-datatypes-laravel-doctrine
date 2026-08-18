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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use PHPUnit\Framework\TestCase;

/**
 * Shared setup for the Type unit tests.
 *
 * The Types are exercised against a REAL platform rather than a mock: the whole contract of a
 * Doctrine Type is what it hands the platform and what it makes of what comes back, and a
 * mocked platform would only assert that this package agrees with itself.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
abstract class AbstractTypeTestCase extends TestCase
{
    protected AbstractPlatform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platform = new MySQLPlatform();
    }
}

<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

namespace HraDigital\Tests\Components\DatatypesDoctrine\Unit;

use HraDigital\Components\DatatypesDoctrine\DoctrineTypes;
use HraDigital\Components\DatatypesDoctrine\Types\AbstractDatatypeType;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function class_exists;
use function count;
use function is_a;

class DoctrineTypesTest extends TestCase
{
    public function testTheDefaultMapIsTheDatatypesAndTheNativesTogether(): void
    {
        $this->assertSame(
            count(DoctrineTypes::datatypes()) + count(DoctrineTypes::natives()),
            count(DoctrineTypes::defaults())
        );
    }

    public function testEveryMappedClassExistsAndIsOneOfThisPackagesTypes(): void
    {
        foreach (DoctrineTypes::defaults() as $name => $class) {
            $this->assertTrue(class_exists($class), "{$class} does not exist.");
            $this->assertTrue(
                is_a($class, AbstractDatatypeType::class, true),
                "{$class} is not a Type shipped by this package."
            );
        }
    }

    public function testEveryTypeIsRegisteredUnderItsOwnClassShortName(): void
    {
        // The naming convention IS the API: an attribute declared as `Money $total` maps with
        // `type: 'MoneyType'`, so a mismatch here silently breaks a documented mapping.
        foreach (DoctrineTypes::defaults() as $name => $class) {
            $this->assertSame($name, $class::NAME, "{$class} is registered under the wrong name.");
            $this->assertSame($name, (new $class())->getName());
        }
    }

    public function testTheDatatypesAndNativeSetsDoNotCollide(): void
    {
        $collisions = [];

        foreach (array_keys(DoctrineTypes::natives()) as $name) {
            if (isset(DoctrineTypes::datatypes()[$name])) {
                $collisions[] = $name;
            }
        }

        $this->assertSame([], $collisions);
    }
}

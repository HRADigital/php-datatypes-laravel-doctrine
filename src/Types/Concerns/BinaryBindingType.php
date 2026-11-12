<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

namespace HraDigital\Components\DatatypesDoctrine\Types\Concerns;

use Doctrine\DBAL\ParameterType;

use function enum_exists;

// One trait per DBAL major, declared conditionally - only one of the two ever exists at runtime.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
if (enum_exists(ParameterType::class)) {
    /**
     * Binds a Type's value as binary, so the driver never treats the raw bytes as text.
     *
     * DBAL 4 turned ParameterType into an enum and typed `getBindingType()` to return it; DBAL 3
     * keeps it as a class of int constants with an untyped `getBindingType()`. No single method
     * signature is valid on both, so the trait is declared once per major.
     *
     * @internal
     *
     * @package   HraDigital\Components\DatatypesDoctrine
     * @copyright HraDigital\Components\DatatypesDoctrine
     * @license   MPL-2.0
     */
    trait BinaryBindingType
    {
        public function getBindingType(): ParameterType
        {
            return ParameterType::BINARY;
        }
    }
} else {
    /**
     * Binds a Type's value as binary, so the driver never treats the raw bytes as text.
     *
     * DBAL 3 variant - see the DBAL 4 variant above for the split.
     *
     * @internal
     *
     * @package   HraDigital\Components\DatatypesDoctrine
     * @copyright HraDigital\Components\DatatypesDoctrine
     * @license   MPL-2.0
     */
    trait BinaryBindingType
    {
        public function getBindingType(): int
        {
            return ParameterType::BINARY;
        }
    }
}

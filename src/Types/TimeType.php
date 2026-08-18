<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

namespace HraDigital\Components\DatatypesDoctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Maps HraDigital\Datatypes\Datetime\Datetime onto a TIME column.
 *
 * The date part resets to the epoch on read, so the same stored time always rehydrates to the
 * same instant - see AbstractDatetimeType::resetsUnparsedFields().
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class TimeType extends AbstractDatetimeType
{
    public const NAME = 'TimeType';

    /** @inheritDoc */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getTimeTypeDeclarationSQL($column);
    }

    protected function formatString(AbstractPlatform $platform): string
    {
        return $platform->getTimeFormatString();
    }

    protected function resetsUnparsedFields(): bool
    {
        return true;
    }
}

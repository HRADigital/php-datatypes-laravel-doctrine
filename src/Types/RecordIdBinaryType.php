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
use HraDigital\Components\DatatypesDoctrine\Types\Concerns\BinaryBindingType;
use HraDigital\Datatypes\ValueObjects\RecordId;

use function bin2hex;

/**
 * Maps HraDigital\Datatypes\ValueObjects\RecordId onto a BINARY(16) column - BYTEA on PostgreSQL.
 *
 * Stored as the 16 raw bytes, so the column and its indexes are less than half the size of the
 * string form, and a v7 UUID keeps them insert-ordered. The width is always 16 - a RecordId is
 * never any other size - so a `length` in the mapping is ignored. A stored value that fails is
 * shown hex-encoded in the exception message.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class RecordIdBinaryType extends AbstractRecordIdType
{
    use BinaryBindingType;

    public const NAME = 'RecordIdBinaryType';

    /** @inheritDoc */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = RecordId::BYTES_LENGTH;
        $column['fixed'] = true;

        return $platform->getBinaryTypeDeclarationSQL($column);
    }

    protected function toColumn(RecordId $id): string
    {
        return $id->toBytes();
    }

    protected function fromColumn(string $value): RecordId
    {
        return $this->datatypeClass()::fromBytes($value);
    }

    protected function printable(string $value): string
    {
        return bin2hex($value);
    }
}

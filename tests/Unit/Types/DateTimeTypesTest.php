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

use DateTimeImmutable as NativeDateTimeImmutable;
use Doctrine\DBAL\Types\ConversionException;
use HraDigital\Components\DatatypesDoctrine\Types\AbstractDatetimeType;
use HraDigital\Components\DatatypesDoctrine\Types\DateImmutableType;
use HraDigital\Components\DatatypesDoctrine\Types\DateTimeImmutableType;
use HraDigital\Components\DatatypesDoctrine\Types\DateTimeType;
use HraDigital\Components\DatatypesDoctrine\Types\DateTimeTzImmutableType;
use HraDigital\Components\DatatypesDoctrine\Types\DateTimeTzType;
use HraDigital\Components\DatatypesDoctrine\Types\DateType;
use HraDigital\Components\DatatypesDoctrine\Types\TimeImmutableType;
use HraDigital\Components\DatatypesDoctrine\Types\TimeType;
use HraDigital\Datatypes\Datetime\DateTime;
use HraDigital\Datatypes\Datetime\DatetimeContract;
use HraDigital\Datatypes\Datetime\DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_map;

/**
 * The eight Datatypes date/time Types share all of their conversion logic, so they are proven
 * together - what differs per Type is only the rehydrated class, the column and the format string.
 */
class DateTimeTypesTest extends AbstractTypeTestCase
{
    /**
     * @return array<string, array{AbstractDatetimeType, class-string<DatetimeContract>}>
     */
    public static function datetimeTypes(): array
    {
        return [
            'DateTimeType'          => [new DateTimeType(), DateTime::class],
            'DateTimeImmutableType' => [new DateTimeImmutableType(), DateTimeImmutable::class],
        ];
    }

    /**
     * @return array<string, array{AbstractDatetimeType, class-string<DatetimeContract>}>
     */
    public static function dateTypes(): array
    {
        return [
            'DateType'          => [new DateType(), DateTime::class],
            'DateImmutableType' => [new DateImmutableType(), DateTimeImmutable::class],
        ];
    }

    /**
     * @return array<string, array{AbstractDatetimeType, class-string<DatetimeContract>}>
     */
    public static function timeTypes(): array
    {
        return [
            'TimeType'          => [new TimeType(), DateTime::class],
            'TimeImmutableType' => [new TimeImmutableType(), DateTimeImmutable::class],
        ];
    }

    /**
     * @return array<string, array{AbstractDatetimeType, class-string<DatetimeContract>}>
     */
    public static function allTypes(): array
    {
        return self::datetimeTypes() + self::dateTypes() + self::timeTypes() + [
            'DateTimeTzType'          => [new DateTimeTzType(), DateTime::class],
            'DateTimeTzImmutableType' => [new DateTimeTzImmutableType(), DateTimeImmutable::class],
        ];
    }

    /**
     * @return array<string, array{AbstractDatetimeType}>
     */
    public static function types(): array
    {
        return array_map(static fn (array $set): array => [$set[0]], self::allTypes());
    }

    public function testEachNameFollowsItsClassShortName(): void
    {
        $this->assertSame('DateTimeType', DateTimeType::NAME);
        $this->assertSame('DateTimeTzType', DateTimeTzType::NAME);
        $this->assertSame('DateType', DateType::NAME);
        $this->assertSame('TimeType', TimeType::NAME);
        $this->assertSame('DateTimeImmutableType', DateTimeImmutableType::NAME);
        $this->assertSame('DateTimeTzImmutableType', DateTimeTzImmutableType::NAME);
        $this->assertSame('DateImmutableType', DateImmutableType::NAME);
        $this->assertSame('TimeImmutableType', TimeImmutableType::NAME);
    }

    public function testEachDeclaresItsOwnColumn(): void
    {
        $column = ['name' => 'a_column'];

        $this->assertSame('DATETIME', (new DateTimeType())->getSQLDeclaration($column, $this->platform));
        $this->assertSame('DATE', (new DateType())->getSQLDeclaration($column, $this->platform));
        $this->assertSame('TIME', (new TimeType())->getSQLDeclaration($column, $this->platform));
        $this->assertSame('DATETIME', (new DateTimeImmutableType())->getSQLDeclaration($column, $this->platform));
        $this->assertSame('DATE', (new DateImmutableType())->getSQLDeclaration($column, $this->platform));
        $this->assertSame('TIME', (new TimeImmutableType())->getSQLDeclaration($column, $this->platform));

        // MySQL has no timezone-aware column, so DBAL falls back to a plain DATETIME here and
        // the offset is NOT stored. Documented on DateTimeTzType.
        $this->assertSame('DATETIME', (new DateTimeTzType())->getSQLDeclaration($column, $this->platform));
        $this->assertSame('DATETIME', (new DateTimeTzImmutableType())->getSQLDeclaration($column, $this->platform));
    }

    /**
     * @param class-string<DatetimeContract> $class
     */
    #[DataProvider('datetimeTypes')]
    public function testDatetimeRoundTripsThroughBothConversions(AbstractDatetimeType $type, string $class): void
    {
        $original = $class::fromString('2026-05-27 14:32:11');

        $stored = $type->convertToDatabaseValue($original, $this->platform);
        $this->assertSame('2026-05-27 14:32:11', $stored);

        $restored = $type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf($class, $restored);
        $this->assertTrue($original->equals($restored));
    }

    /**
     * @param class-string<DatetimeContract> $class
     */
    #[DataProvider('dateTypes')]
    public function testADateColumnResetsTheTimeSoTheSameRowAlwaysRehydratesTheSame(
        AbstractDatetimeType $type,
        string $class
    ): void {
        $stored = $type->convertToDatabaseValue($class::fromString('2026-05-27 14:32:11'), $this->platform);
        $this->assertSame('2026-05-27', $stored);

        // Without the reset, the unparsed hour/minute/second would inherit the current
        // wall clock and two reads of the very same row would not be equal.
        $restored = $type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf($class, $restored);
        $this->assertSame('2026-05-27 00:00:00', (string) $restored);
    }

    /**
     * @param class-string<DatetimeContract> $class
     */
    #[DataProvider('timeTypes')]
    public function testATimeColumnResetsTheDate(AbstractDatetimeType $type, string $class): void
    {
        $stored = $type->convertToDatabaseValue($class::fromString('2026-05-27 14:32:11'), $this->platform);
        $this->assertSame('14:32:11', $stored);

        $restored = $type->convertToPHPValue($stored, $this->platform);
        $this->assertInstanceOf($class, $restored);
        $this->assertSame('1970-01-01 14:32:11', (string) $restored);
    }

    /**
     * @param class-string<DatetimeContract> $class
     */
    #[DataProvider('datetimeTypes')]
    public function testAnyNativeDateTimeIsPersistableAndComesBackAsADatatype(
        AbstractDatetimeType $type,
        string $class
    ): void {
        $stored = $type->convertToDatabaseValue(new NativeDateTimeImmutable('2026-05-27 14:32:11'), $this->platform);
        $this->assertSame('2026-05-27 14:32:11', $stored);

        $restored = $type->convertToPHPValue(new NativeDateTimeImmutable('2026-05-27 14:32:11'), $this->platform);
        $this->assertInstanceOf($class, $restored);
        $this->assertSame('2026-05-27 14:32:11', (string) $restored);
    }

    public function testTheOtherDatatypesClassIsConvertedToTheMappedOne(): void
    {
        $mutable = DateTime::fromString('2026-05-27 14:32:11');
        $immutable = DateTimeImmutable::fromString('2026-05-27 14:32:11');

        $toImmutable = (new DateTimeImmutableType())->convertToPHPValue($mutable, $this->platform);
        $this->assertInstanceOf(DateTimeImmutable::class, $toImmutable);
        $this->assertTrue($mutable->equals($toImmutable));

        $toMutable = (new DateTimeType())->convertToPHPValue($immutable, $this->platform);
        $this->assertInstanceOf(DateTime::class, $toMutable);
        $this->assertTrue($immutable->equals($toMutable));
    }

    /**
     * @param class-string<DatetimeContract> $class
     */
    #[DataProvider('datetimeTypes')]
    public function testFallsBackToFreeParsingWhenTheDriverDoesNotUseThePlatformFormat(
        AbstractDatetimeType $type,
        string $class
    ): void {
        // Drivers hand back fractional seconds and differing offsets; the platform format
        // does not match those, and the value still has to load.
        $restored = $type->convertToPHPValue('2026-05-27T14:32:11+00:00', $this->platform);

        $this->assertInstanceOf($class, $restored);
        $this->assertSame('2026-05-27 14:32:11', (string) $restored);
    }

    #[DataProvider('types')]
    public function testNullPassesThroughUntouchedInBothDirections(AbstractDatetimeType $type): void
    {
        $this->assertNull($type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($type->convertToPHPValue(null, $this->platform));
    }

    /**
     * @param class-string<DatetimeContract> $class
     */
    #[DataProvider('allTypes')]
    public function testADatatypeAlreadyRehydratedPassesThroughUntouched(AbstractDatetimeType $type, string $class): void
    {
        $datetime = $class::fromString('2026-05-27 14:32:11');

        $this->assertSame($datetime, $type->convertToPHPValue($datetime, $this->platform));
    }

    #[DataProvider('types')]
    public function testRejectsANonStringColumnValue(AbstractDatetimeType $type): void
    {
        $this->expectException(ConversionException::class);

        $type->convertToPHPValue(20260527, $this->platform);
    }

    #[DataProvider('types')]
    public function testRejectsAValueItCannotPersist(AbstractDatetimeType $type): void
    {
        $this->expectException(ConversionException::class);

        $type->convertToDatabaseValue('2026-05-27', $this->platform);
    }

    #[DataProvider('types')]
    public function testAMalformedRowSurfacesAsAConversionFailure(AbstractDatetimeType $type): void
    {
        $this->expectException(ConversionException::class);

        $type->convertToPHPValue('not a datetime', $this->platform);
    }
}

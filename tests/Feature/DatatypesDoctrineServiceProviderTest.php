<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

namespace HraDigital\Tests\Components\DatatypesDoctrine\Feature;

use Doctrine\DBAL\Types\Type;
use HraDigital\Components\DatatypesDoctrine\DatatypesDoctrineServiceProvider;
use HraDigital\Components\DatatypesDoctrine\DoctrineTypes;
use HraDigital\Components\DatatypesDoctrine\Types\EmailAddressType;
use HraDigital\Components\DatatypesDoctrine\Types\MoneyType;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;

/**
 * Proves the package's central promise: install it, and every Type is usable in an Entity
 * mapping with nothing published, nothing added to config/app.php and nothing added to the
 * Doctrine config.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class DatatypesDoctrineServiceProviderTest extends TestCase
{
    /**
     * @param Application $app
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [DatatypesDoctrineServiceProvider::class];
    }

    public function testEveryShippedTypeIsRegisteredWithDoctrineOnBootWithNoConfiguration(): void
    {
        foreach (DoctrineTypes::defaults() as $name => $class) {
            $this->assertTrue(Type::hasType($name), "{$name} was not registered with Doctrine.");
            $this->assertInstanceOf($class, Type::getType($name));
        }
    }

    public function testATypeIsResolvableByTheNameAnEntityMappingWouldUse(): void
    {
        // This is exactly what `#[ORM\Column(type: 'EmailAddressType')]` resolves through.
        $this->assertInstanceOf(EmailAddressType::class, Type::getType('EmailAddressType'));
        $this->assertInstanceOf(MoneyType::class, Type::getType('MoneyType'));
    }

    public function testThePackageConfigIsMergedInWithoutBeingPublished(): void
    {
        $this->assertTrue($this->app['config']->get('datatypes-doctrine.enabled'));
        $this->assertSame(
            DoctrineTypes::defaults(),
            $this->app['config']->get('datatypes-doctrine.types')
        );
    }

    public function testTheConfigIsPublishableForApplicationsThatWantToEditTheMap(): void
    {
        $paths = DatatypesDoctrineServiceProvider::pathsToPublish(
            DatatypesDoctrineServiceProvider::class,
            DatatypesDoctrineServiceProvider::PUBLISH_TAG
        );

        $this->assertNotEmpty($paths);
    }

    public function testTheMapIsMirroredIntoLaravelDoctrinesOwnConfigWhenThatPackageIsPresent(): void
    {
        // Simulates laravel-doctrine/orm being installed: it publishes a `doctrine` config,
        // and its console commands read the custom types from there.
        $this->app['config']->set('doctrine.custom_types', ['ExistingType' => MoneyType::class]);

        (new DatatypesDoctrineServiceProvider($this->app))->register();

        $custom = $this->app['config']->get('doctrine.custom_types');

        // The application's own entry survives - first registration wins.
        $this->assertSame(MoneyType::class, $custom['ExistingType']);
        $this->assertSame(EmailAddressType::class, $custom['EmailAddressType']);
    }

    public function testRegisteringTwiceInTheSameProcessIsHarmless(): void
    {
        // Doctrine's registry is static and outlives the container - queue workers, Octane and
        // the test suite itself all boot the provider more than once in one process.
        (new DatatypesDoctrineServiceProvider($this->app))->register();
        (new DatatypesDoctrineServiceProvider($this->app))->register();

        $this->assertInstanceOf(EmailAddressType::class, Type::getType('EmailAddressType'));
    }
}

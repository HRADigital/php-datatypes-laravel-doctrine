<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

namespace HraDigital\Components\DatatypesDoctrine;

use Doctrine\DBAL\Types\Type;
use HraDigital\Components\DatatypesDoctrine\Types\AbstractDatatypeType;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;

use function class_exists;
use function config_path;
use function is_a;
use function is_array;
use function is_string;

/**
 * Wires every Doctrine Type this package ships into the application.
 *
 * Discovered automatically through composer's `extra.laravel.providers`, and registers from a
 * config the package merges in itself - so an application only has to `composer require` this
 * package to start using the Types in its Entity mappings. There is nothing to publish, nothing
 * to add to `config/app.php` and nothing to add to the Doctrine config.
 *
 * Registration happens in register() rather than boot(): Doctrine's Type registry has to be
 * populated before any EntityManager resolves a mapping, and another provider's boot() may
 * already be doing that.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class DatatypesDoctrineServiceProvider extends ServiceProvider
{
    /** Config key the package's settings live under. */
    public const CONFIG_KEY = 'datatypes-doctrine';

    /** Publish tag, for the applications that do want to edit the map. */
    public const PUBLISH_TAG = 'datatypes-doctrine-config';

    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), self::CONFIG_KEY);

        /** @var ConfigRepository $config */
        $config = $this->app->make('config');

        if ($config->get(self::CONFIG_KEY . '.enabled', true) !== true) {
            return;
        }

        $types = $this->resolveTypeMap($config);

        $this->registerWithDoctrine($types);
        $this->registerWithLaravelDoctrine($config, $types);
    }

    public function boot(): void
    {
        // Only worth wiring up when there is an artisan command to run it.
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes(
            [$this->configPath() => config_path(self::CONFIG_KEY . '.php')],
            self::PUBLISH_TAG
        );
    }

    /**
     * Reads the configured map, dropping anything that is not a usable Type class.
     *
     * A published config is application-owned and can drift - a renamed class, a typo, a Type
     * removed from a later release of this package. Skipping a bad entry keeps the container
     * booting; letting it through would blow up later, inside Doctrine, far from the cause.
     *
     * @return array<string, class-string<AbstractDatatypeType>>
     */
    private function resolveTypeMap(ConfigRepository $config): array
    {
        $configured = $config->get(self::CONFIG_KEY . '.types', []);

        if (! is_array($configured)) {
            return DoctrineTypes::defaults();
        }

        $types = [];

        foreach ($configured as $name => $class) {
            if (! is_string($name) || $name === '' || ! is_string($class)) {
                continue;
            }

            if (! class_exists($class) || ! is_a($class, AbstractDatatypeType::class, true)) {
                continue;
            }

            $types[$name] = $class;
        }

        return $types;
    }

    /**
     * Populates Doctrine's global Type registry. This is what actually makes
     * `#[ORM\Column(type: 'hra_email_address')]` resolve in a consuming application.
     *
     * @param array<string, class-string<AbstractDatatypeType>> $types
     */
    private function registerWithDoctrine(array $types): void
    {
        foreach ($types as $name => $class) {
            // The registry is static and outlives the container, so on a second boot in the
            // same process (tests, queue workers, octane) the name is already taken - by us.
            // A name taken by ANOTHER package is left alone on purpose: silently swapping
            // someone else's Type would corrupt their columns. Rename ours in the config.
            if (Type::hasType($name)) {
                continue;
            }

            Type::addType($name, $class);
        }
    }

    /**
     * Mirrors the map into laravel-doctrine/orm's own `doctrine.custom_types`.
     *
     * Not what makes the Types work - registerWithDoctrine() already did that - but it keeps
     * the package's console commands (schema tool, migrations diff) reporting the same set of
     * types the runtime uses. Skipped entirely when that package is absent.
     *
     * @param array<string, class-string<AbstractDatatypeType>> $types
     */
    private function registerWithLaravelDoctrine(ConfigRepository $config, array $types): void
    {
        if (! $config->has('doctrine')) {
            return;
        }

        $existing = $config->get('doctrine.custom_types', []);

        if (! is_array($existing)) {
            $existing = [];
        }

        // Application-declared entries win: an application that already mapped a name to its
        // own Type keeps it, matching the "first registration wins" rule above.
        $config->set('doctrine.custom_types', $existing + $types);
    }

    private function configPath(): string
    {
        return __DIR__ . '/../config/' . self::CONFIG_KEY . '.php';
    }
}

<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Lock\Tests\Store;

use Symfony\Component\Lock\Exception\InvalidArgumentException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\Store\PostgreSqlStore;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @requires extension pdo_pgsql
 * @group integration
 */
class PostgreSqlStoreTest extends AbstractStoreTest
{
    use BlockingStoreTestTrait;
    use SharedLockStoreTestTrait;

    /**
     * {@inheritdoc}
     */
    public function getStore(): PersistingStoreInterface
    {
        if (!getenv('POSTGRES_HOST')) {
            $this->markTestSkipped('Missing POSTGRES_HOST env variable');
        }

        return new PostgreSqlStore('pgsql:host='.getenv('POSTGRES_HOST'), ['db_username' => 'postgres', 'db_password' => 'password']);
    }

    /**
     * @requires extension pdo_sqlite
     */
    public function testInvalidDriver()
    {
        $store = new PostgreSqlStore('sqlite:/tmp/foo.db');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The adapter "Symfony\Component\Lock\Store\PostgreSqlStore" does not support');
        $store->exists(new Key('foo'));
    }
}

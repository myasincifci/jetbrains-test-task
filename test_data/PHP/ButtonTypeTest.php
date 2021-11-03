<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\Type;

use Symfony\Component\Form\Button;
use Symfony\Component\Form\Exception\BadMethodCallException;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ButtonTypeTest extends BaseTypeTest
{
    public const TESTED_TYPE = 'Symfony\Component\Form\Extension\Core\Type\ButtonType';

    public function testCreateButtonInstances()
    {
        $this->assertInstanceOf(Button::class, $this->factory->create(static::TESTED_TYPE));
    }

    /**
     * @param string $emptyData
     * @param null   $expectedData
     */
    public function testSubmitNullUsesDefaultEmptyData($emptyData = 'empty', $expectedData = null)
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Buttons do not support empty data.');
        parent::testSubmitNullUsesDefaultEmptyData($emptyData, $expectedData);
    }
}

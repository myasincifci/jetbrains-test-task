<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Validator\Type;

use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;

class DateTypeValidatorExtensionTest extends BaseValidatorExtensionTest
{
    use ExpectDeprecationTrait;
    use ValidatorExtensionTrait;

    protected function createForm(array $options = [])
    {
        return $this->factory->create(DateType::class, null, $options);
    }

    public function testInvalidMessage()
    {
        $form = $this->createForm();

        $this->assertSame('Please enter a valid date.', $form->getConfig()->getOption('invalid_message'));
    }

    /**
     * @group legacy
     */
    public function testLegacyInvalidMessage()
    {
        $this->expectDeprecation('Since symfony/form 5.2: Setting the "legacy_error_messages" option to "true" is deprecated. It will be disabled in Symfony 6.0.');

        $form = $this->createForm([
            'legacy_error_messages' => true,
        ]);

        $this->assertSame('This value is not valid.', $form->getConfig()->getOption('invalid_message'));
    }
}

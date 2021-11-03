<?php

namespace Symfony\Component\Validator\Tests\Constraints;

use Symfony\Component\Validator\Constraints\Isin;
use Symfony\Component\Validator\Constraints\IsinValidator;
use Symfony\Component\Validator\Constraints\Luhn;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class IsinValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new IsinValidator();
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new Isin());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid()
    {
        $this->validator->validate('', new Isin());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider getValidIsin
     */
    public function testValidIsin($isin)
    {
        $this->validator->validate($isin, new Isin());
        $this->expectViolationsAt(0, $isin, new Luhn());
        $this->assertNoViolation();
    }

    public function getValidIsin()
    {
        return [
            ['XS2125535901'], // Goldman Sachs International
            ['DE000HZ8VA77'], // UniCredit Bank AG
            ['CH0528261156'], // Leonteq Securities AG [Guernsey]
            ['US0378331005'], // Apple, Inc.
            ['AU0000XVGZA3'], // TREASURY CORP VICTORIA 5 3/4% 2005-2016
            ['GB0002634946'], // BAE Systems
            ['CH0528261099'], // Leonteq Securities AG [Guernsey]
            ['XS2155672814'], // OP Corporate Bank plc
            ['XS2155687259'], // Orbian Financial Services III, LLC
            ['XS2155696672'], // Sheffield Receivables Company LLC
        ];
    }

    /**
     * @dataProvider getIsinWithInvalidLenghFormat
     */
    public function testIsinWithInvalidFormat($isin)
    {
        $this->assertViolationRaised($isin, Isin::INVALID_LENGTH_ERROR);
    }

    public function getIsinWithInvalidLenghFormat()
    {
        return [
            ['X'],
            ['XS'],
            ['XS2'],
            ['XS21'],
            ['XS215'],
            ['XS2155'],
            ['XS21556'],
            ['XS215569'],
            ['XS2155696'],
            ['XS21556966'],
            ['XS215569667'],
        ];
    }

    /**
     * @dataProvider getIsinWithInvalidPattern
     */
    public function testIsinWithInvalidPattern($isin)
    {
        $this->assertViolationRaised($isin, Isin::INVALID_PATTERN_ERROR);
    }

    public function getIsinWithInvalidPattern()
    {
        return [
            ['X12155696679'],
            ['123456789101'],
            ['XS215569667E'],
            ['XS215E69667A'],
        ];
    }

    /**
     * @dataProvider getIsinWithValidFormatButIncorrectChecksum
     */
    public function testIsinWithValidFormatButIncorrectChecksum($isin)
    {
        $this->expectViolationsAt(0, $isin, new Luhn());
        $this->assertViolationRaised($isin, Isin::INVALID_CHECKSUM_ERROR);
    }

    public function getIsinWithValidFormatButIncorrectChecksum()
    {
        return [
            ['XS2112212144'],
            ['DE013228VA77'],
            ['CH0512361156'],
            ['XS2125660123'],
            ['XS2012587408'],
            ['XS2012380102'],
            ['XS2012239364'],
        ];
    }

    private function assertViolationRaised($isin, $code)
    {
        $constraint = new Isin([
            'message' => 'myMessage',
        ]);

        $this->validator->validate($isin, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$isin.'"')
            ->setCode($code)
            ->assertRaised();
    }
}

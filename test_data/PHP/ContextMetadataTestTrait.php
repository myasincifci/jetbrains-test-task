<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Normalizer\Features;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Test context handling from Serializer metadata.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
trait ContextMetadataTestTrait
{
    public function testContextMetadataNormalize()
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizer = new ObjectNormalizer($classMetadataFactory, null, null, new PhpDocExtractor());
        new Serializer([new DateTimeNormalizer(), $normalizer]);

        $dummy = new ContextMetadataDummy();
        $dummy->date = new \DateTime('2011-07-28T08:44:00.123+00:00');

        self::assertEquals(['date' => '2011-07-28T08:44:00+00:00'], $normalizer->normalize($dummy));

        self::assertEquals(['date' => '2011-07-28T08:44:00.123+00:00'], $normalizer->normalize($dummy, null, [
            ObjectNormalizer::GROUPS => 'extended',
        ]), 'a specific normalization context is used for this group');

        self::assertEquals(['date' => '2011-07-28T08:44:00+00:00'], $normalizer->normalize($dummy, null, [
            ObjectNormalizer::GROUPS => 'simple',
        ]), 'base denormalization context is unchanged for this group');
    }

    public function testContextMetadataContextDenormalize()
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizer = new ObjectNormalizer($classMetadataFactory, null, null, new PhpDocExtractor());
        new Serializer([new DateTimeNormalizer(), $normalizer]);

        /** @var ContextMetadataDummy $dummy */
        $dummy = $normalizer->denormalize(['date' => '2011-07-28T08:44:00+00:00'], ContextMetadataDummy::class);
        self::assertEquals(new \DateTime('2011-07-28T08:44:00+00:00'), $dummy->date);

        /** @var ContextMetadataDummy $dummy */
        $dummy = $normalizer->denormalize(['date' => '2011-07-28T08:44:00+00:00'], ContextMetadataDummy::class, null, [
            ObjectNormalizer::GROUPS => 'extended',
        ]);
        self::assertEquals(new \DateTime('2011-07-28T08:44:00+00:00'), $dummy->date, 'base denormalization context is unchanged for this group');

        /** @var ContextMetadataDummy $dummy */
        $dummy = $normalizer->denormalize(['date' => '28/07/2011'], ContextMetadataDummy::class, null, [
            ObjectNormalizer::GROUPS => 'simple',
        ]);
        self::assertEquals('2011-07-28', $dummy->date->format('Y-m-d'), 'a specific denormalization context is used for this group');
    }
}

class ContextMetadataDummy
{
    /**
     * @var \DateTime
     *
     * @Groups({ "extended", "simple" })
     * @Context({ DateTimeNormalizer::FORMAT_KEY = \DateTime::RFC3339 })
     * @Context(
     *     normalizationContext = { DateTimeNormalizer::FORMAT_KEY = \DateTime::RFC3339_EXTENDED },
     *     groups = {"extended"}
     * )
     * @Context(
     *     denormalizationContext = { DateTimeNormalizer::FORMAT_KEY = "d/m/Y" },
     *     groups = {"simple"}
     * )
     */
    public $date;
}

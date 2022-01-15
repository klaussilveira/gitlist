<?php

declare(strict_types=1);

namespace GitList\App\Form;

use GitList\SCM\Commit\Criteria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CriteriaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('from', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('to', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('author', TextType::class, [
                'required' => false,
            ])
            ->add('message', TextType::class, [
                'required' => false,
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Criteria::class,
        ]);
    }
}

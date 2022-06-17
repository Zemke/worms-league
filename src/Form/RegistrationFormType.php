<?php

namespace App\Form;

use App\Entity\User;
use App\Form\PasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('email', EmailType::class)
            ->add('plainPassword', PasswordType::class)
            ->add('wormnet', TextType::class, [
                'mapped' => false,
                'label' => 'WormNET channel',
                'attr' => ['placeholder' => 'Name a WormNET channel',],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^#?(ag|anything ?goes|party ?time|pt|rh|ropers ?heaven)$/i',
                        'message' => 'Name one of the prominent channels in WormNET',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

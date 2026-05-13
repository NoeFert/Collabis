<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('offerType', ChoiceType::class, [
                'choices' => ['Offre' => 'offre', 'Demande' => 'demande'],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Type d’annonce',
                'data' => $options['offer_type'],
            ])
            ->add('description', TextareaType::class)
            ->add('category', ChoiceType::class, [
                'mapped' => false,
                'choices' => [
                    'Informatique' => 'informatique',
                    'Soutien scolaire' => 'soutien_scolaire',
                    'Bricolage' => 'bricolage',
                    'Logement' => 'logement',
                    'Autre' => 'autre',
                ],
                'data' => $options['category_key'],
            ])
            ->add('location', TextType::class, [
                'required' => false,
                'label' => 'Localisation',
                'attr' => ['placeholder' => 'Ex. Genève...']
            ])
            ->add('images', FileType::class, [
                'label' => 'Photos',
                'mapped' => false,
                'multiple' => true,
                'required' => false,
                'constraints' => [
                    new All([
                        new Image(['maxSize' => '5M'])
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'category_key' => 'demande',
            'offer_type' => 'demande',
        ]);
    }
}
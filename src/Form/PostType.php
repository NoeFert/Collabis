<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'label',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'by_reference' => false,
                'label' => 'Catégories',
                'query_builder' => fn ($repository) => $repository->createQueryBuilder('c')
                    ->orderBy('c.label', 'ASC'),
            ])
            ->add('images', FileType::class, [
                'label' => 'Télécharger des photos',
                'mapped' => false,     // On dit à Symfony que ce champ n'est pas dans la table Post
                'multiple' => true,    // Permet de choisir plusieurs fichiers
                'required' => false,
                'constraints' => [
                    new All([
                        new Image([
                            'maxSize' => '5M',
                            'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                            'mimeTypesMessage' => 'Format invalide (JPG, PNG ou WEBP uniquement)',
                        ])
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}

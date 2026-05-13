<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Media;
use App\Entity\Post;
use App\Entity\UserProfile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // --- 1. CRÉATION DES CATÉGORIES ---
        $categories = [];
        $dataCategories = [
            '"demande"' => 'demande',
            'Informatique' => 'informatique',
            'Soutien scolaire' => 'soutien_scolaire',
            'Bricolage' => 'bricolage',
            'Logement' => 'logement',
            'Autre' => 'autre',
            'Transport' => 'transport'
        ];

        foreach ($dataCategories as $label => $key) {
            $category = new Category();
            $category->setLabel($label);
            $category->setCategoryKey($key);
            $manager->persist($category);
            $categories[] = $category;
        }

        // --- 2. CRÉATION DES UTILISATEURS ---
        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = new UserProfile();
            $user->setUsername($faker->userName());
            $user->setEmail("user$i@example.com");
            
            $hashedPassword = $this->userPasswordHasher->hashPassword($user, 'password');
            $user->setPassword($hashedPassword);
            
            $manager->persist($user);
            $users[] = $user;
        }

        // --- 3. CRÉATION DES POSTS ET DES MÉDIAS (VERSION MOSAÏQUE) ---
        for ($j = 0; $j < 20; $j++) {
            $post = new Post();
            $post->setTitle($faker->sentence(4));
            $post->setDescription($faker->paragraph(3));
            
            $randomUser = $faker->randomElement($users);
            $post->setUser($randomUser);

            $randomCat = $faker->randomElement($categories);
            $post->addCategory($randomCat);

            $manager->persist($post);

            // ICI ON GÉNÈRE LES 3 IMAGES POUR TA MAQUETTE
            for ($m = 0; $m < 3; $m++) {
                $media = new Media();
                // On crée une URL unique pour chaque image
                $media->setUrl("https://picsum.photos/800/600?random=" . ($j * 3 + $m));
                $media->setPost($post);
                
                $manager->persist($media);
            }
        }

        // --- 4. ENVOI FINAL ---
        $manager->flush();
    }
}
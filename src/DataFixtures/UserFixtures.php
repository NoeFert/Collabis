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
        // On utilise le Faker en français pour avoir de jolis textes
        $faker = Factory::create('fr_FR');

        // --- 1. CRÉATION DES CATÉGORIES ---
        $categories = [];
        $dataCategories = [
            'Informatique' => 'info',
            'Soutien Scolaire' => 'school',
            'Bricolage' => 'brico',
            'Logement' => 'home',
            'Transport' => 'car'
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
            
            // Hachage du mot de passe "password"
            $hashedPassword = $this->userPasswordHasher->hashPassword($user, 'password');
            $user->setPassword($hashedPassword);
            
            $manager->persist($user);
            $users[] = $user;
        }

        // --- 3. CRÉATION DES POSTS ET DES MÉDIAS ---
        for ($j = 0; $j < 20; $j++) {
            $post = new Post();
            $post->setTitle($faker->sentence(4));
            $post->setDescription($faker->paragraph(3));
            
            // On lie le post à un utilisateur au hasard
            $randomUser = $faker->randomElement($users);
            $post->setUser($randomUser);

            // On ajoute une catégorie au hasard (relation ManyToMany)
            $randomCat = $faker->randomElement($categories);
            $post->addCategory($randomCat);

            $manager->persist($post);

            // Pour chaque post, on crée 1 ou 2 images (Médias)
            for ($m = 0; $m < $faker->numberBetween(1, 2); $m++) {
                $media = new Media();
                // On utilise Picsum pour avoir des images de nature/tech différentes
                $media->setUrl("https://picsum.photos/seed/" . $faker->uuid . "/800/600");
                $media->setPost($post); // On lie l'image au post
                
                $manager->persist($media);
            }
        }

        // --- 4. ENVOI FINAL ---
        $manager->flush();
    }
}
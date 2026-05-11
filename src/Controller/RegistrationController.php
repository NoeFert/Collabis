<?php

namespace App\Controller;

use App\Entity\UserProfile;
use App\Form\RegistrationFormType;
use App\Security\AppCustomAuthenticator;
use App\Service\BlobStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager, SluggerInterface $slugger, BlobStorage $blobStorage): Response
    {
        $user = new UserProfile();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatar')->getData();

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            if ($avatarFile) {
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                // On nettoie le nom du fichier (enlève les espaces, accents, etc.)
                $safeFilename = $slugger->slug($originalFilename);
                // On ajoute un ID unique pour éviter que deux fichiers aient le même nom
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $avatarFile->guessExtension();

                try {
                    $avatarUrl = $blobStorage->uploadPublic($avatarFile, 'avatars/'.$newFilename);
                    $user->setAvatarUrl($avatarUrl);
                } catch (FileException $e) {
                    // Ici on pourrait gérer l'erreur si l'upload échoue
                }
            }

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            return $security->login($user, AppCustomAuthenticator::class, 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}

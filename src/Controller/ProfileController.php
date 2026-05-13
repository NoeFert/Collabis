<?php

namespace App\Controller;

use App\Entity\UserProfile;
use App\Form\EditProfileType;
use App\Repository\PostRepository;
use App\Service\BlobStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProfileController extends AbstractController
{

    // Profil personnel (réservé à l'utilisateur connecté)
    #[Route('/my-profile', name: 'app_my_profile')]
    #[IsGranted('ROLE_USER')]
    public function myProfile(PostRepository $postRepository): Response
    {
        $user = $this->getUser();

        return $this->render('profile/my_profile.html.twig', [
            'controller_name' => 'ProfileController',
            'posts' => $user instanceof UserProfile ? $postRepository->findBy(['user' => $user], ['id' => 'DESC']) : [],
        ]);
    }

    // Édition du profil personnel
    #[Route('/my-profile/edit', name: 'app_edit_profile')]
    #[IsGranted('ROLE_USER')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, BlobStorage $blobStorage): Response
    {
        $user = $this->getUser();

        if (!$user instanceof UserProfile) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EditProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de l'avatar
            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatar')->getData();
            if ($avatarFile) {
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                // On nettoie le nom du fichier
                $safeFilename = $slugger->slug($originalFilename);
                // On ajoute un ID unique
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $avatarFile->guessExtension();

                try {
                    $avatarUrl = $blobStorage->uploadPublic($avatarFile, 'avatars/'.$newFilename);
                    $user->setAvatarUrl($avatarUrl);
                } catch (\Exception $e) {
                    // Gérer l'erreur si l'upload échoue
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès!');
            return $this->redirectToRoute('app_my_profile');
        }

        return $this->render('profile/edit_profile.html.twig', [
            'form' => $form,
        ]);
    }

    // Profil public (accessible à tous)
    #[Route('/profile/{id}', name: 'app_public_profile')]
    public function publicProfile(UserProfile $userProfile): Response
    {
        return $this->render('profile/public_profile.html.twig', [
            'controller_name' => 'ProfileController',
            'user' => $userProfile,
        ]);
    }
}

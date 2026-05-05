<?php

namespace App\Controller;

use App\Entity\UserProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProfileController extends AbstractController
{

    // Profil personnel (réservé à l'utilisateur connecté)
    #[Route('/my-profile', name: 'app_my_profile')]
    #[IsGranted('ROLE_USER')]
    public function myProfile(): Response
    {
        return $this->render('profile/my_profile.html.twig', [
            'controller_name' => 'ProfileController',
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

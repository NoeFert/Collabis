<?php

namespace App\Controller;

use App\Entity\Conversation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConversationController extends AbstractController
{
    #[Route('/conversation', name: 'app_conversation')]
    public function index(): Response
    {
        return $this->render('conversation/index.html.twig', [
            'controller_name' => 'ConversationController',
        ]);
    }

    public function show(Conversation $conversation): Response
    {
        return $this->render('conversation/show.html.twig', [
            'controller_name' => 'ConversationController',
            'conversation' => $conversation,
        ]);
    }
}

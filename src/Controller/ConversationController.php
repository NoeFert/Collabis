<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Post;
use App\Entity\UserProfile;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ConversationController extends AbstractController
{
    #[Route('/conversation', name: 'app_conversation', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(ConversationRepository $conversationRepository): Response
    {
        $user = $this->getCurrentUserProfile();

        return $this->render('conversation/index.html.twig', [
            'conversations' => $conversationRepository->findForUser($user),
        ]);
    }

    #[Route('/conversation/start/{id}', name: 'app_conversation_start', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function start(
        Post $post,
        Request $request,
        ConversationRepository $conversationRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $requester = $this->getCurrentUserProfile();
        $owner = $post->getUser();

        if (!$owner instanceof UserProfile) {
            throw $this->createNotFoundException('Le propriétaire de cette annonce est introuvable.');
        }

        if (!$this->isCsrfTokenValid('start_conversation'.$post->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($owner->getId() === $requester->getId()) {
            $this->addFlash('warning', 'Vous ne pouvez pas vous contacter sur votre propre annonce.');

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        $conversation = $conversationRepository->findOneForPostAndUsers($post, $requester, $owner);

        if (!$conversation) {
            $conversation = (new Conversation())
                ->setPost($post)
                ->setSubject($post->getTitle())
                ->setUserProfile($requester)
                // ->setInterlocuteur($owner)
// fix migration)
                ->setInterlocutor($owner);

            $entityManager->persist($conversation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_conversation_show', ['id' => $conversation->getId()]);
    }

    #[Route('/conversation/{id}', name: 'app_conversation_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Conversation $conversation): Response
    {
        $user = $this->getCurrentUserProfile();
        $this->denyAccessUnlessParticipant($conversation, $user);

        return $this->render('conversation/show.html.twig', [
            'conversation' => $conversation,
        ]);
    }

    #[Route('/conversation/{id}/message', name: 'app_conversation_message', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function message(Conversation $conversation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getCurrentUserProfile();
        $this->denyAccessUnlessParticipant($conversation, $user);

        if (!$this->isCsrfTokenValid('send_message'.$conversation->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $content = trim((string) $request->request->get('content', ''));

        if ($content === '') {
            $this->addFlash('warning', 'Votre message ne peut pas être vide.');

            return $this->redirectToRoute('app_conversation_show', ['id' => $conversation->getId()]);
        }

        $message = (new Message())
            ->setConversation($conversation)
            ->setSender($user)
            ->setContent($content)
            ->setDatetime(new \DateTimeImmutable());

        $entityManager->persist($message);
        $entityManager->flush();

        return $this->redirectToRoute('app_conversation_show', ['id' => $conversation->getId()]);
    }

    private function getCurrentUserProfile(): UserProfile
    {
        $user = $this->getUser();

        if (!$user instanceof UserProfile) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        return $user;
    }

    private function denyAccessUnlessParticipant(Conversation $conversation, UserProfile $user): void
    {
        $participantIds = array_filter([
            $conversation->getUserProfile()?->getId(),
// (fix migration)
            $conversation->getInterlocutor()?->getId(),
        ]);

        if (!in_array($user->getId(), $participantIds, true)) {
            throw $this->createAccessDeniedException('Vous ne participez pas à cette conversation.');
        }
    }
}

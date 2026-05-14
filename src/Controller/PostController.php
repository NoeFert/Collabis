<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Category;
use App\Entity\Media;
use App\Entity\UserProfile;
use App\Form\PostType;
use App\Repository\CategoryRepository;
use App\Repository\ConversationRepository;
use App\Repository\PostRepository;
use App\Service\BlobStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/post')]
final class PostController extends AbstractController
{
    #[Route(name: 'app_post_index', methods: ['GET'])]
    public function index(Request $request, PostRepository $postRepository): Response
    {
        $offerFilter = $request->query->get('offer');
        $search = trim((string) $request->query->get('q', ''));

        $qb = $postRepository->createQueryBuilder('p')
            ->leftJoin('p.categories', 'c')
            ->addSelect('c')
            ->orderBy('p.id', 'DESC');

        if (in_array($offerFilter, ['offre', 'demande'], true)) {
            $qb->andWhere('p.offer_type = :offerType')
               ->setParameter('offerType', $offerFilter);
        }

        if ($search !== '') {
            $qb->andWhere('p.title LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        return $this->render('post/index.html.twig', [
            'posts' => $qb->getQuery()->getResult(),
            'offer_filter' => $offerFilter,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, BlobStorage $blobStorage, CategoryRepository $categoryRepository): Response
    {
        $post = new Post();
        $post->setUser($this->getUser());

        $form = $this->createForm(PostType::class, $post, ['category_key' => 'demande', 'offer_type' => 'demande']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncSubmittedPostData($post, $form, $entityManager, $slugger, $blobStorage, $categoryRepository);

            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_post_index');
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', ['post' => $post]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager, SluggerInterface $slugger, BlobStorage $blobStorage, CategoryRepository $categoryRepository): Response
    {
        if (!$this->isPostOwner($post)) {
            throw $this->createAccessDeniedException();
        }

        $category = $post->getCategories()->first() ?: null;
        $form = $this->createForm(PostType::class, $post, [
            'category_key' => $category ? $category->getCategoryKey() : 'demande',
            'offer_type' => $post->getOfferType() ?: 'demande',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncSubmittedPostData($post, $form, $entityManager, $slugger, $blobStorage, $categoryRepository);
            $entityManager->flush();

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_post_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager, ConversationRepository $conversationRepository): Response
    {
        if (!$this->isPostOwner($post)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            foreach ($conversationRepository->findBy(['post' => $post]) as $conversation) {
                $conversation->setPost(null);
            }

            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_my_profile');
    }

    private function isPostOwner(Post $post): bool
    {
        $user = $this->getUser();

        return $user instanceof UserProfile && $post->getUser()?->getId() === $user->getId();
    }

    private function syncSubmittedPostData(Post $post, FormInterface $form, EntityManagerInterface $entityManager, SluggerInterface $slugger, BlobStorage $blobStorage, CategoryRepository $categoryRepository): void
    {
        $categoryKey = $form->get('category')->getData() ?: 'demande';
        $category = $this->findOrCreateCategory($categoryKey, $entityManager, $categoryRepository);

        foreach ($post->getCategories()->toArray() as $existingCategory) {
            $post->removeCategory($existingCategory);
        }
        $post->addCategory($category);

        $imageFiles = $form->get('images')->getData();
        if (!$imageFiles) {
            return;
        }

        foreach ($imageFiles as $imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
            $imageUrl = $blobStorage->uploadPublic($imageFile, 'posts/'.$newFilename);

            $media = new Media();
            $media->setUrl($imageUrl);
            $media->setPost($post);
            $entityManager->persist($media);
        }
    }

    private function findOrCreateCategory(string $categoryKey, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Category
    {
        $category = $categoryRepository->findOneBy(['category_key' => $categoryKey]);
        if (!$category) {
            $category = new Category();
            $category->setCategoryKey($categoryKey);
            $category->setLabel(ucfirst($categoryKey));
            $entityManager->persist($category);
        }
        return $category;
    }
}

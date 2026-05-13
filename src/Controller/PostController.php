<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Media; // Importation de l'entité Media
use App\Entity\UserProfile;
use App\Form\PostType;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Service\BlobStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface; // Importation du Slugger

#[Route('/post')]
final class PostController extends AbstractController
{
    #[Route(name: 'app_post_index', methods: ['GET'])]
    public function index(Request $request, PostRepository $postRepository, CategoryRepository $categoryRepository): Response
    {
        $selectedCategoryKey = $request->query->getString('category');
        $searchQuery = trim($request->query->getString('q'));
        $selectedCategory = $selectedCategoryKey !== ''
            ? $categoryRepository->findOneBy(['category_key' => $selectedCategoryKey])
            : null;

        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findForCategory($selectedCategory, $searchQuery),
            'categories' => $categoryRepository->findBy([], ['label' => 'ASC']),
            'selected_category_key' => $selectedCategory?->getCategoryKey(),
            'search_query' => $searchQuery,
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, BlobStorage $blobStorage, CategoryRepository $categoryRepository): Response
    {
        $post = new Post();
        $post->setUser($this->getUser());

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // --- DEBUT DE LA LOGIQUE IMAGE ---
            $imageFiles = $form->get('images')->getData(); // Récupère les fichiers du formulaire

            if ($imageFiles) {
                foreach ($imageFiles as $imageFile) {
                    // On crée un nom de fichier unique
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    $imageUrl = $blobStorage->uploadPublic($imageFile, 'posts/'.$newFilename);

                    // On crée l'enregistrement dans la table Media
                    $media = new Media();
                    $media->setUrl($imageUrl);
                    $media->setPost($post); // On lie l'image au post actuel
                    
                    $entityManager->persist($media);
                }
            }
            // --- FIN DE LA LOGIQUE IMAGE ---

            $this->syncCategoriesFromRequest($post, $request, $categoryRepository);

            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        $this->denyAccessUnlessPostOwner($post);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncCategoriesFromRequest($post, $request, $categoryRepository);
            $entityManager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_post_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessPostOwner($post);

        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Vérifie que l'utilisateur connecté est le propriétaire du post.
     * Si ce n'est pas le cas, une exception AccessDeniedException est levée.
     * 
     * @param Post $post Le post à vérifier
     * @throws AccessDeniedException Si l'utilisateur n'est pas le propriétaire du post
     * @return void
     */
    private function denyAccessUnlessPostOwner(Post $post): void
    {
        $user = $this->getUser();

        if (!$user instanceof UserProfile || $post->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres annonces.');
        }
    }

    private function syncCategoriesFromRequest(Post $post, Request $request, CategoryRepository $categoryRepository): void
    {
        $postData = $request->request->all('post');
        $categoryIds = array_filter(array_map('intval', $postData['categories'] ?? []));

        foreach ($post->getCategories()->toArray() as $category) {
            $post->removeCategory($category);
        }

        if ($categoryIds === []) {
            return;
        }

        foreach ($categoryRepository->findBy(['id' => $categoryIds]) as $category) {
            $post->addCategory($category);
        }
    }
}

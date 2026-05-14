<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Imports nécessaires pour la route de secours (Mise à jour BDD)
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PostRepository $postRepository): Response
    {
        // On récupère les 6 dernières annonces pour l'accueil (Design Bento)
        $lastPosts = $postRepository->findBy([], ['id' => 'DESC'], 6);

        return $this->render('home/index.html.twig', [
            'posts' => $lastPosts,
        ]);
    }

    /**
     * ROUTE DE SECOURS : À supprimer une fois que le site fonctionne en ligne !
     * Cette route force la base de données à se synchroniser avec tes entités PHP.
     */
    #[Route('/maintenance/update-db', name: 'app_update_db')]
    public function updateDb(KernelInterface $kernel): Response
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        // On utilise 'schema:update --force' pour ignorer l'historique des migrations
        // et créer directement la colonne 'location' manquante.
        $input = new ArrayInput([
            'command' => 'doctrine:schema:update',
            '--force' => true,
            '--complete' => true,
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        return new Response("<pre>Exécution de la mise à jour forcée :\n\n" . $output->fetch() . "\n\nSi 'Database schema updated successfully' s'affiche, ton site est réparé !</pre>");
    }
}
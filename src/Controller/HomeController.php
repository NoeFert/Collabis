<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Imports pour la console interne
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PostRepository $postRepository): Response
    {
        // Récupération des 6 derniers posts pour le design Bento
        $lastPosts = $postRepository->findBy([], ['id' => 'DESC'], 6);

        return $this->render('home/index.html.twig', [
            'posts' => $lastPosts,
        ]);
    }

    /**
     * ROUTE DE SECOURS : Synchronise la base de données Infomaniak
     * URL : http://www.collabis.ch/maintenance/update-db
     */
    #[Route('/maintenance/update-db', name: 'app_update_db')]
    public function updateDb(KernelInterface $kernel): Response
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $output = new BufferedOutput();

        // Étape 1 : On vide le cache des métadonnées (pour que Doctrine lise le nouveau Post.php)
        $application->run(new ArrayInput(['command' => 'doctrine:cache:clear-metadata']), $output);

        // Étape 2 : On force la mise à jour du schéma SQL
        $input = new ArrayInput([
            'command' => 'doctrine:schema:update',
            '--force' => true,
        ]);
        
        $application->run($input, $output);

        $result = $output->fetch();

        return new Response("<pre>--- Rapport de mise à jour ---\n\n" . $result . "\n\n------------------------------\nSi vous voyez 'Database schema updated successfully', c'est gagné !</pre>");
    }
}
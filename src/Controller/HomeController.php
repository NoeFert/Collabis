<?php

namespace App\Controller;

// Tes anciens imports sont ici...
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// AJOUTE CEUX-LÀ ICI (Ils vont s'allumer dès que tu colles la fonction plus bas)
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PostRepository $postRepository): Response
    {
        $lastPosts = $postRepository->findBy([], ['id' => 'DESC'], 6);

        return $this->render('home/index.html.twig', [
            'posts' => $lastPosts,
        ]);
    }

    // COLLE LA NOUVELLE FONCTION ICI
    #[Route('/maintenance/update-db', name: 'app_update_db')]
    public function updateDb(KernelInterface $kernel): Response
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        return new Response("<pre>Mise à jour terminée :\n" . $output->fetch() . "</pre>");
    }
}
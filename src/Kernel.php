<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Cette méthode dit à Symfony d'écrire le cache dans /tmp sur le serveur
     */
    public function getCacheDir(): string
    {
        // Si on est sur un serveur type Vercel ou en production, on utilise /tmp
        if (isset($_SERVER['VERCEL_URL']) || $this->getEnvironment() === 'prod') {
            return '/tmp/cache/' . $this->getEnvironment();
        }

        return parent::getCacheDir();
    }

    /**
     * Idem pour les fichiers de logs
     */
    public function getLogDir(): string
    {
        if (isset($_SERVER['VERCEL_URL']) || $this->getEnvironment() === 'prod') {
            return '/tmp/log';
        }

        return parent::getLogDir();
    }
}
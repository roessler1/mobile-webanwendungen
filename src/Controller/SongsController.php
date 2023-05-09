<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\SongRepository;
use Detection\MobileDetect;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class SongsController extends AbstractController
{

    #[Route('/')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('homepage');
    }

    #[Route('/{_locale<%app.supported_locales%>}/', name: 'homepage')]
    public function index(SongRepository $songRepository): Response
    {
        $_locale = 'en';
        return $this->render('index.html.twig');
    }

    #[Route('/player/', name: 'player')]
    public function getPlayer(): Response
    {
        return $this->render('player.html.twig', []);
    }
}

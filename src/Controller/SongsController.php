<?php

namespace App\Controller;

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
    public function index(Environment $twig): Response
    {
        $_locale = 'en';
        return $this->render('index.html.twig', [
        ]);
    }
}

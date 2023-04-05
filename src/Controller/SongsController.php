<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class SongsController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Environment $twig): Response
    {
        return new Response($twig->render('index.html.twig', [
        ]));
    }
}

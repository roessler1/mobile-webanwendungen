<?php

namespace App\Controller;


use Detection\MobileDetect;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class MainController extends AbstractController
{

    public $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    #[Route('/')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('homepage');
    }

    #[Route('/{_locale<%app.supported_locales%>}/', name: 'homepage', options: ['expose'=>true])]
    public function index(Request $request): Response
    {
        $_locale = 'en';
        if($request->isXmlHttpRequest()) {
            return new Response($this->twig->resolveTemplate('index.html.twig')->renderBlock('main'));
        }
        return $this->render('index.html.twig');
    }

    #[Route('/player/', name: 'player')]
    public function getPlayer(): Response
    {
        return $this->render('player.html.twig')->setMaxAge(86400);
    }
}

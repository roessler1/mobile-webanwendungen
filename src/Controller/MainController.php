<?php

namespace App\Controller;


use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\TrackRepository;
use Detection\MobileDetect;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
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
    #[Cache(maxage: 3600, public: true, mustRevalidate: false)]
    public function getPlayer(): Response
    {
        return $this->render('player.html.twig');
    }

    #[Route('/en/404exception', name: 'not_found_exception')]
    #[Cache(maxage: 86400, public: true, mustRevalidate: false)]
    public function getNotFoundException(): Response
    {
        return $this->render('not_found_exception.html.twig');
    }

    #[Route('/{_locale<%app.supported_locales%>}/search', name: 'search', options: ['expose'=>true])]
    public function search(Request $request): Response
    {
       if($request->isXmlHttpRequest()) {
           return new Response($this->twig->resolveTemplate('search.html.twig')->renderBlock('main'));
       }
       return $this->render('search.html.twig');
    }

    #[Route('/results', name: 'results', options: ['expose' => true])]
    public function results(Request $request, ArtistRepository $artists, AlbumRepository $albums, TrackRepository $tracks): Response
    {
        $search = $request->request->get('search');
        return new Response($this->twig->render('results.html.twig', [
            'artists' => $artists->findArtists($search),
            'albums' => $albums->findAlbums($search),
            'tracks' => $tracks->findTracks($search),
        ]));
    }
}

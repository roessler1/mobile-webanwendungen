<?php

namespace App\Controller;


use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\TrackRepository;
use App\Repository\UserRepository;
use Detection\MobileDetect;
use Doctrine\ORM\NonUniqueResultException;
use http\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

    #[Route('/{_locale<%app.supported_locales%>}/home', name: 'home')]
    public function home(): Response
    {
        return $this->render('homepage.html.twig');
    }

    #[Route('/{_locale<%app.supported_locales%>}/signin', name: 'signin')]
    public function signin(): Response
    {
        return $this->render('signin.html.twig');
    }

    #[Route('/{_locale<%app.supported_locales%>}/signup', name: 'signup')]
    public function signup(): Response
    {
        return $this->render('signup.html.twig');
    }

    #[Route('/signup', name: 'createUser')]
    public function createUser(Request $request, UserRepository $users): RedirectResponse
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $retypedPassword = $request->request->get('retypedPassword');
        echo $username . ", " . $password . ", " . $retypedPassword;

        if($password === $retypedPassword && $users->findUser($username) != null) {
            $cookie = new Cookie(
                'username',
                $username,
                time() + (30 * 24 * 60 * 60)
            );
            $response = new Response();
            $response->headers->setCookie($cookie);

            $cookie = new Cookie(
                'password',
                $password,
                time() + (30 * 24 * 60 * 60)
            );
            $response->headers->setCookie($cookie);
            $response->send();
            return $this->redirectToRoute('homepage');
        }
        return $this->redirectToRoute('signup');
    }
}

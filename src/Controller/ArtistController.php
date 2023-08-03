<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Repository\AlbumRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ArtistController extends AbstractController
{

    public $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    #[Route('/{_locale<%app.supported_locales%>}/artist/{id}', name: 'artist', options: ['expose' => true])]
    public function index(Request $request, Artist $artist, AlbumRepository $albumRepository, UsersRepository $users): Response
    {
        if ($users->checkIdentity($request->cookies->get('username'), $request->cookies->get('password')) != "") {
            if ($request->isXmlHttpRequest()) {
                return new Response($this->twig->resolveTemplate('artist.html.twig')->renderBlock('main', [
                    'artist' => $artist,
                    'albums' => $albumRepository->findArtistAlbums($artist),
                    'eps' => $albumRepository->findArtistEPs($artist),
                    'singles' => $albumRepository->findArtistSingles($artist),
                ]));
            }
            return $this->render('artist.html.twig', [
                'artist' => $artist,
                'albums' => $albumRepository->findArtistAlbums($artist),
                'eps' => $albumRepository->findArtistEPs($artist),
                'singles' => $albumRepository->findArtistSingles($artist),
            ]);
        } else {
            return $this->redirectToRoute('home');
        }
    }
}

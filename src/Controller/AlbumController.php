<?php

namespace App\Controller;

use App\Entity\Album;
use App\Repository\TrackRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class AlbumController extends AbstractController
{

    public $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    #[Route('/{_locale<%app.supported_locales%>}/album/{id}', name: 'album', options: ['expose'=>true])]
    public function index(Request $request, Album $album, TrackRepository $trackRepository, UsersRepository $users): Response
    {
        if ($users->checkIdentity($request->cookies->get('username'), $request->cookies->get('password')) != "") {
            if ($request->isXmlHttpRequest()) {
                return new Response($this->twig->resolveTemplate('album.html.twig')->renderBlock('main', [
                    'album' => $album,
                    'tracks' => $trackRepository->findBy(array('album' => $album), array('track_number' => 'ASC'))
                ]));
            }
            return $this->render('album.html.twig', [
                'album' => $album,
                'tracks' => $trackRepository->findBy(array('album' => $album), array('track_number' => 'ASC'))
            ]);
        } else {
            return $this->redirectToRoute('home');
        }
    }
}

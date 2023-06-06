<?php

namespace App\Controller;

use App\Entity\Album;
use App\Repository\TrackRepository;
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
    #[Cache(maxage: 86400, public: false, mustRevalidate: false)]
    public function index(Request $request, Album $album, TrackRepository $trackRepository): Response
    {
        if($request->isXmlHttpRequest()) {
            return new Response($this->twig->resolveTemplate('album.html.twig')->renderBlock('main', [
                'album' => $album,
                'tracks' => $trackRepository->findBy(array('album' => $album), array('track_number' => 'ASC'))
            ]));
        }
        return $this->render('album.html.twig', [
            'album' => $album,
            'tracks' => $trackRepository->findBy(array('album' => $album), array('track_number' => 'ASC'))
        ]);
    }
}

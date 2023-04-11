<?php

namespace App\Controller;

use App\Entity\Album;
use App\Repository\SongRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AlbumController extends AbstractController
{
    #[Route('/album/{id}', name: 'album')]
    public function index(Album $album, SongRepository $songRepository): Response
    {
        return $this->render('album.html.twig', [
            'album' => $album,
            'songs' => $songRepository->findBy(array('album' => $album), array('track_number' => 'ASC')),
        ]);
    }
}

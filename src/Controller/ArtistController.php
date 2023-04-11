<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use App\Repository\AlbumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArtistController extends AbstractController
{
    #[Route('/artist/{id}', name: 'artist')]
    public function index(Artist $artist, AlbumRepository $albumRepository): Response
    {
        return $this->render('artist.html.twig', [
            'artist' => $artist,
            'albums' => $albumRepository->findBy(array('artist' => $artist), array('name' => 'ASC')),
        ]);
    }
}

<?php

namespace App\Controller;


use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\TrackRepository;
use App\Repository\UsersRepository;
use Detection\MobileDetect;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use function PHPUnit\Framework\isEmpty;

class MainController extends AbstractController
{
    public $twig;

    public function __construct(Environment $twig, private ManagerRegistry $doctrine)
    {
        $this->twig = $twig;
    }

    #[Route('/')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('homepage');
    }

    #[Route('/{_locale<%app.supported_locales%>}/', name: 'homepage', options: ['expose'=>true])]
    public function index(Request $request, UsersRepository $users, AlbumRepository $albumRepository): Response
    {
        if($users->checkIdentity($request->cookies->get('username'), $request->cookies->get('password')) != []) {
            $_locale = 'en';
            $username = $request->cookies->get('username');
            $lastAlbums = implode(",",$this->doctrine->getConnection()->fetchAssociative("SELECT last_albums FROM users WHERE username = E'$username'"));
            $lastAlbums= explode(",",trim($lastAlbums, '{}'));

            $albums = [];
            if($lastAlbums[0] !== "") {
                foreach ($lastAlbums as $nr) {
                    $ALBUM_QUERY = "SELECT * FROM album WHERE id = $nr";
                    $res = $this->doctrine->getConnection()->fetchAssociative($ALBUM_QUERY);
                    $albums[] = $res;
                }
            }
            $lastArtists = implode(",",$this->doctrine->getConnection()->fetchAssociative("SELECT last_artists FROM users WHERE username = E'$username'"));
            $lastArtists= explode(",",trim($lastArtists, '{}'));

            $artists = [];
            if($lastArtists[0] !== "") {
                foreach ($lastArtists as $nr) {
                    $ARTIST_QUERY = "SELECT * FROM artist WHERE id = $nr";
                    $res = $this->doctrine->getConnection()->fetchAssociative($ARTIST_QUERY);
                    $artists[] = $res;
                }
            }
            if ($request->isXmlHttpRequest()) {
                return new Response($this->twig->resolveTemplate('index.html.twig')->renderBlock('main', [
                    'albums' => $albums,
                    'artists' => $artists,
                ]));
            }
            return $this->render('index.html.twig', [
                'albums' => $albums,
                'artists' => $artists,
            ]);
        } else {
            return $this->redirectToRoute('home');
        }
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
    public function search(Request $request, UsersRepository $users): Response
    {
        if ($users->checkIdentity($request->cookies->get('username'), $request->cookies->get('password')) != []) {
            if ($request->isXmlHttpRequest()) {
                return new Response($this->twig->resolveTemplate('search.html.twig')->renderBlock('main'));
            }
            return $this->render('search.html.twig');
        } else {
            return $this->redirectToRoute('home');
        }
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
    public function home(Request $request, UsersRepository $users): Response
    {
        if ($users->checkIdentity($request->cookies->get('username'), $request->cookies->get('password')) === []) {
            return $this->render('homepage.html.twig');
        } else {
            return $this->redirectToRoute('homepage');
        }
    }

    #[Route('/{_locale<%app.supported_locales%>}/signin', name: 'signin')]
    public function signin(Request $request, UsersRepository $users): Response
    {
        if ($users->checkIdentity($request->cookies->get('username'), $request->cookies->get('password')) === []) {
            return $this->render('signin.html.twig');
        } else {
            return $this->redirectToRoute('homepage');
        }
    }

    #[Route('/{_locale<%app.supported_locales%>}/signup', name: 'signup')]
    public function signup(Request $request, UsersRepository $users): Response
    {
        if ($users->checkIdentity($request->cookies->get('username'), $request->cookies->get('password')) === []) {
            return $this->render('signup.html.twig');
        } else {
            return $this->redirectToRoute('homepage');
        }
    }

    #[Route('/signup', name: 'createUser')]
    public function createUser(Request $request, UsersRepository $users): RedirectResponse
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $retypedPassword = $request->request->get('retypedPassword');

        if($password === $retypedPassword && $users->findUser($username) != "") {
            $USER_ROW = "INSERT INTO users(id, username, password, admin, last_artists, last_albums) VALUES(nextval('users_id_seq'),  E'$username', 
                                             E'$password', false, '{}', '{}')";
            $statement = $this->doctrine->getConnection()->prepare($USER_ROW);
            $statement->execute();

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

    #[Route('/login', name: 'login')]
    public function login(Request $request, UsersRepository $users): RedirectResponse
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');

        if($users->checkIdentity($username, $password) != []) {
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
        return $this->redirectToRoute('signin');
    }

    #[Route('/signout', name: 'signout')]
    public function signout(): RedirectResponse
    {
        $response = new Response();
        $response->headers->clearCookie('username');
        $response->headers->clearCookie('password');
        $response->send();

        return $this->redirectToRoute('home');
    }

    #[Route('/lastalbum', name: 'lastalbum', options: ['expose' => true])]
    public function lastAlbum(Request $request, UsersRepository $users): Response
    {
        $username = $request->cookies->get('username');
        $lastAlbums = implode(",",$this->doctrine->getConnection()->fetchAssociative("SELECT last_albums FROM users WHERE username = E'$username'"));
        $lastAlbums= explode(",",trim($lastAlbums, '{}'));
        if($lastAlbums[0] === "") array_shift($lastAlbums);
        if(!in_array($request->request->get('alb_id'), $lastAlbums)) {
            if(sizeof($lastAlbums) === 4)
                array_shift($lastAlbums);
            array_push($lastAlbums, $request->request->get('alb_id'));
            $lastAlbums = implode(",", $lastAlbums);
            $USER_ROW = "UPDATE users SET last_albums = '{$lastAlbums}' WHERE username = '$username'";
            $statement = $this->doctrine->getConnection()->prepare($USER_ROW);
            $statement->execute();
        }
        return new Response();
    }

    #[Route('/lastartist', name: 'lastartist', options: ['expose' => true])]
    public function lastArtist(Request $request, UsersRepository $users): Response
    {
        $username = $request->cookies->get('username');
        $lastArtist = implode(",",$this->doctrine->getConnection()->fetchAssociative("SELECT last_artists FROM users WHERE username = E'$username'"));
        $lastArtist= explode(",",trim($lastArtist, '{}'));
        if($lastArtist[0] === "") array_shift($lastArtist);
        if(!in_array($request->request->get('art_id'), $lastArtist)) {
            if(sizeof($lastArtist) === 4)
                array_shift($lastArtist);
            array_push($lastArtist, $request->request->get('art_id'));
            $lastArtist = implode(",", $lastArtist);
            $USER_ROW = "UPDATE users SET last_artists = '{$lastArtist}' WHERE username = '$username'";
            $statement = $this->doctrine->getConnection()->prepare($USER_ROW);
            $statement->execute();
        }
        return new Response();
    }
}

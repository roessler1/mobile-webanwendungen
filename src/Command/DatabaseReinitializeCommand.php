<?php

namespace App\Command;

use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\TrackRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use getID3;

#[AsCommand(
    name: 'app:database:reinitialize',
    description: 'Add a short description for your command',
)]
class DatabaseReinitializeCommand extends Command
{

    private $audio_dir;

    public function __construct(
        private ArtistRepository $artistRespository,
        private AlbumRepository $albumRepository,
        private TrackRepository $trackRepository,
        private ManagerRegistry $doctrine,
        $audio_directory,
    ) {
        parent::__construct();
        $this->audio_dir = 'public'.$audio_directory.'/';
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->resetTables();
        $this->initializeArtists();

        $io->success('SUCCESS!');

        return Command::SUCCESS;
    }

    private function resetTables(): void
    {
        $this->trackRepository->resetTrackDB();
        $this->albumRepository->resetAlbumDB();
        $this->artistRespository->resetArtistDB();

        $SEQ_ARTIST_RESET = "ALTER SEQUENCE artist_id_seq RESTART WITH 1";
        $SEQ_ALBUM_RESET = "ALTER SEQUENCE album_id_seq RESTART WITH 1";
        $SEQ_TRACK_RESET = "ALTER SEQUENCE track_id_seq RESTART WITH 1";
        $statement = $this->doctrine->getConnection()->prepare($SEQ_ARTIST_RESET);
        $statement->execute();
        $statement = $this->doctrine->getConnection()->prepare($SEQ_ALBUM_RESET);
        $statement->execute();
        $statement = $this->doctrine->getConnection()->prepare($SEQ_TRACK_RESET);
        $statement->execute();
    }

    private function initializeArtists(): void
    {
        foreach (scandir($this->audio_dir) as $artistDir) {
            if($artistDir === ".." or $artistDir === "." or $artistDir === "desktop.ini") continue;

            $ARTIST_ROW = "INSERT INTO artist(id, name, picture) VALUES(nextval('artist_id_seq'),  E'$artistDir', 
                                             E'/$artistDir/cover.jpg')";
            $statement = $this->doctrine->getConnection()->prepare($ARTIST_ROW);
            $statement->execute();
            $artist_id = $this->doctrine->getConnection()->fetchOne("Select id FROM artist WHERE name = E'$artistDir'");
            foreach (scandir($this->audio_dir.$artistDir) as $albumDir) {
                if($albumDir === ".." or $albumDir === "." or is_file($this->audio_dir.$artistDir.'/'.$albumDir)) continue;
                $this->initializeAlbums($artist_id, $artistDir, $albumDir);
            }
        }
    }

    private function initializeAlbums($artist_id, $artistDir, $albumDir): void
    {
        $ALBUM = substr(str_replace("'", "\'", $albumDir), 7);
        $albumDirFormatted = str_replace("'", "\\\\\'", $albumDir);
        $ALBUM_YEAR = intval(substr($albumDir, 1, 4));
        $ALBUM_ROW = "INSERT INTO album(id, name, cover, artist_id, year_created, ep, single) VALUES(
                nextval('album_id_seq'),
                E'$ALBUM',
                E'/$artistDir/$albumDirFormatted/cover.jpg',
                $artist_id,
                $ALBUM_YEAR,
                false,
                false
        )";
        $statement = $this->doctrine->getConnection()->prepare($ALBUM_ROW);
        $statement->execute();
        $album_id = $this->doctrine->getConnection()->fetchOne("Select id FROM album WHERE name = E'$ALBUM' AND year_created = $ALBUM_YEAR");
        $this->initializeTracks($album_id, $artistDir."/".$albumDir);
    }
    private function initializeTracks($album_id, $albumDir): void
    {
        $getID3 = new getID3();
        $getID3->encoding = 'UTF-8';
        foreach (scandir($this->audio_dir.$albumDir) as $trackDir) {
            if(is_dir($trackDir) or str_contains($trackDir, ".jpg")) continue;

            $tracks = $getID3->analyze('E:/Music/'.$albumDir."/".$trackDir);

            if(!isset($tracks["tags"])) continue;

            $duration = intval($tracks["playtime_seconds"]);
            $title = "";
            $nr = 0;

            foreach ($tracks["tags"] as $tag) {
                $title = str_replace("'", "''", $tag["title"][0]);
                if(isset($tag["tracknumber"])) $nr = $tag["tracknumber"][0];
                if(isset($tag["track_number"])) $nr = $tag["track_number"][0];
                break;
            }
            $albumDirFormatted = str_replace("'", "''", $albumDir);
            $trackDirFormatted = str_replace("'", "''", $trackDir);

            $TRACK_ROW = "INSERT INTO track(id, name, duration, album_id, track_number, path) VALUES(
                nextval('track_id_seq'),
                E'$title',
                $duration,
                $album_id,
                $nr,
                E'/$albumDirFormatted/$trackDirFormatted'
            )";
            $statement = $this->doctrine->getConnection()->prepare($TRACK_ROW);
            $statement->execute();
        }
    }
}

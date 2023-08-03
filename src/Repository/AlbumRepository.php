<?php

namespace App\Repository;

use App\Entity\Album;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Album>
 *
 * @method Album|null find($id, $lockMode = null, $lockVersion = null)
 * @method Album|null findOneBy(array $criteria, array $orderBy = null)
 * @method Album[]    findAll()
 * @method Album[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Album::class);
    }

    public function save(Album $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Album $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Album[] Returns an array of Album objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Album
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findAlbums($search)
    {
        $search = '%'.$search.'%';
        return $this->createQueryBuilder('a')
            ->leftJoin('a.artist', 'ar')
            ->where('lower(a.name) LIKE lower(:name)')
            ->orWhere('lower(ar.name) LIKE lower(:name)')
            ->setParameter('name', $search)
            ->orderBy('a.year_created', 'ASC')
            ->setMaxResults(4)
            ->getQuery()
            ->execute();
    }

    public function findArtistAlbums($artist)
    {
        return $this->createQueryBuilder('a')
            ->where('a.artist = :artist')
            ->setParameter('artist', $artist)
            ->andWhere('a.ep = false')
            ->andWhere('a.single = false')
            ->orderBy('a.year_created', 'ASC')
            ->getQuery()
            ->execute();
    }

    public function findArtistEPs($artist)
    {
        return $this->createQueryBuilder('a')
            ->where('a.artist = :artist')
            ->setParameter('artist', $artist)
            ->andWhere('a.ep = true')
            ->andWhere('a.single = false')
            ->orderBy('a.year_created', 'ASC')
            ->getQuery()
            ->execute();
    }

    public function findArtistSingles($artist)
    {
        return $this->createQueryBuilder('a')
            ->where('a.artist = :artist')
            ->setParameter('artist', $artist)
            ->andWhere('a.ep = false')
            ->andWhere('a.single = true')
            ->orderBy('a.year_created', 'ASC')
            ->getQuery()
            ->execute();
    }

    public function resetAlbumDB(): int
    {
        return $this->getQueryBuilder()->delete()->getQuery()->execute();
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('al');
    }
}

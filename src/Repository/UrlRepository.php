<?php

namespace App\Repository;

use App\Entity\Url;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Url>
 *
 * @method Url|null find($id, $lockMode = null, $lockVersion = null)
 * @method Url|null findOneBy(array $criteria, array $orderBy = null)
 * @method Url[]    findAll()
 * @method Url[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UrlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Url::class);
    }

    /**
     * Ajoute l'entitée $entity à la bdd
     *
     * @param Url $entity
     * @param boolean $flush
     * @return void
     */
    public function add(Url $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime l'entitée $entity de la bdd
     *
     * @param Url $entity
     * @param boolean $flush
     * @return void
     */
    public function remove(Url $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Retourne si l'url est déjà présente dans la bdd
     *
     * @param string $url
     * @return boolean
     */
    public function alreadyExists(string $url):bool
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.slug = :url')
            ->setParameter('url', $url)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }

    /**
     * Vide toutes les enregistrements présents dans la bdd
     *
     * @return void
     */
    public function truncate() {
        return $this->createQueryBuilder('u')->delete()->getQuery()->execute();
    }

    /**
     * Retourne l'entitée URL recherchée par son slug
     *
     * @param string $value
     * @return Url|null
     */
    public function findOneBySLug(string $slug): ?Url
    {
       return $this->createQueryBuilder('u')
           ->andWhere('u.slug = :val')
           ->setParameter('val', $slug)
           ->getQuery()
           ->getOneOrNullResult()
       ;
    }
}

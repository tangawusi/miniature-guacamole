<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    public function save(Note $note, bool $flush = false): void
    {
        $this->getEntityManager()->persist($note);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function searchForUser(User $user, ?string $query, ?string $status, ?string $category): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC');

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(n.title) LIKE :query OR LOWER(n.content) LIKE :query')
               ->setParameter('query', '%' . mb_strtolower(trim($query)) . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('n.status = :status')
               ->setParameter('status', $status);
        }

        if ($category !== null && $category !== '') {
            $qb->andWhere('LOWER(n.category) = :category')
               ->setParameter('category', mb_strtolower(trim($category)));
        }

        return $qb->getQuery()->getResult();
    }

    public function getCategoriesForUser(User $user): array
    {
        $rows = $this->createQueryBuilder('n')
            ->select('DISTINCT n.category AS category')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.category', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): string => $row['category'], $rows);
    }
}

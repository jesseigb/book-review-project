<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Review|null find($id, $lockMode = null, $lockVersion = null)
 * @method Review|null findOneBy(array $criteria, array $orderBy = null)
 * @method Review[]    findAll()
 * @method Review[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    // Find all reviews of a user
    public function findUserReviews(int $userID): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r')
            ->where('r.creator = :userID')
            ->setParameter('userID', $userID);

        $query = $qb->getQuery();

        //Return the array with reviews inside it
        return $query->execute();
    }

    // Find a review of a user
    public function findUserReview(int $userID, int $reviewID): Review
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r')
            ->where('r.creator = :userID')
            ->andWhere('r.id = :reviewID')
            ->setParameter('userID', $userID)
            ->setParameter('reviewID', $reviewID);

        $query = $qb->getQuery();

        $review = new Review();

        //Return the Review class inside the array
        foreach($query->execute() as $x) {
            $review = $x;
        }

        return $review;
    }

    // Find random review by ID
    public function findRandomReview($int): array
    {
        //Find books based on the name URL parameter
        $qb = $this->createQueryBuilder('r')
            ->select('r')
            ->where('r.id = :int')
            ->setParameter('int', $int);

        $query = $qb->getQuery();

        $reviewArray = array();

        foreach($query->execute() as $review) {
            array_push($reviewArray,
                array (
                    'title' => $review->getBook()->getTitle(),
                    'author' => $review->getBook()->getAuthor(),
                    'reviewText' => $review->getReview(),
                    'date' => $review->getDate(),
                    'reviewer' => $review->getCreator()->getUsername(),
                )
            );
        }

        return $reviewArray;
    }

}

<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */

class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    // Find the book based on the title on the URL parameter
    public function findByTitle(string $bookname): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.title = :bookname')
            ->setParameter('bookname', $bookname);

        $query = $qb->getQuery();

        //Return the array of all books matches
        return $query->execute();
    }

    // Find the book based on the author on the URL parameter
    public function findByAuthor(string $author): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.author = :author')
            ->setParameter('author', $author);

        $query = $qb->getQuery();

        //Return the array of all books matches
        return $query->execute();
    }

    // Find the book based on the genre on the URL parameter
    public function findByGenre(string $genre): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.genre = :genre')
            ->setParameter('genre', $genre);

        $query = $qb->getQuery();

        //Return the array of all books matches
        return $query->execute();
    }

    // Find the book based on the genre on the URL parameter
    public function findByTitleAndAuthor(string $title, string $author): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.title = :title' && 'b.author = :author')
            ->setParameters (new ArrayCollection([
                new Parameter('title', $title),
                new Parameter('author', $author)
            ]));

        //Return the array of all books matches
        return $qb->getQuery()->getResult();
    }

    // Find the ID of a book based on the name URL parameter
    public function findIDByName(string $bookname): array
    {
        //Find the ID of a book based on the name URL parameter
        $qb = $this->createQueryBuilder('b')
            ->select('b.id')
            ->where('b.title = :bookname')
            ->setParameter('bookname', $bookname);

        $query = $qb->getQuery();

        $bookID = 0;
        // Loop inside the Array and get the ID
        foreach($query->execute() as $id) {
            $bookID = $id;
        }

        //Return the array with ID inside it
        return $bookID;
    }

    public function findBooksByString($str): array
    {
        //Find books based on the name URL parameter
        $qb = $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.title LIKE :str')
            ->setParameter('str', '%'.$str.'%');

        $query = $qb->getQuery();

        $auctionArray = array();

        foreach($query->execute() as $book) {
            array_push($auctionArray,
                array (
                    'title' => $book->getTitle()));
        }

        return $auctionArray;
    }
}

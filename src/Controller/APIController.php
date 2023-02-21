<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Review;
use App\Form\BookType;
use App\Form\ReviewType;
use App\Repository\BookRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class APIController extends AbstractFOSRestController
{

    # ---------------------------- GET Books Section ---------------------------- #

    /**
     * @Rest\Get("/api/v1/books", name="get_books")
     * It returns an array of all books stored into the database
     */
    public function getBooks(ManagerRegistry $registry): Response
    {
        $bookRepository = new BookRepository($registry);
        $data = $bookRepository->findAll();
        $view = $this->view($data, 200);
        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/api/v1/book/{id}", name="get_book")
     * It returns an array of a book stored into the database based on the ID
     */
    public function getBook(int $id, ManagerRegistry $registry): Response
    {
        $bookRepository = new BookRepository($registry);
        $data = $bookRepository->find($id);

        if(!$data) {
            return $this->handleView($this->view("Book Not Found :(", 404));
        }

        $view = $this->view($data, 200);
        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/api/v1/book/title/{bookTitle}", name="get_book_by_title")
     * It returns an array of a book stored into the database
     * Based on the parameters passed a specific book is returned
     */
    public function getBooksByTitle(string $bookTitle, ManagerRegistry $registry): Response
    {
        $bookRepository = new BookRepository($registry);
        if ($bookTitle == '') {
            return $this->redirect('/api/v1/books');
        }

        $data = $bookRepository->findByTitle($bookTitle);

        if(!$data) {
            return $this->handleView($this->view("Book Title Not Found :(", 404));
        }

        $view = $this->view($data, 200);
        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/api/v1/book/author/{authorName}", name="get_book_author")
     * It returns an array of a book stored into the database
     * Based on the parameters passed a specific book is returned
     */
    public function getBooksByAuthor(string $authorName, ManagerRegistry $registry): Response
    {
        $bookRepository = new BookRepository($registry);
        if ($authorName == '') {
            return $this->redirect('/api/v1/books');
        }

        $data = $bookRepository->findByAuthor($authorName);

        if(!$data) {
            return $this->handleView($this->view("Author Not Found :(", 404));
        }

        $view = $this->view($data, 200);
        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/api/v1/book/genre/{genreName}", name="get_book_genre")
     * It returns an array of a book stored into the database
     * Based on the parameters passed a specific book is returned
     */
    public function getBooksByGenre(string $genreName, ManagerRegistry $registry): Response
    {
        $bookRepository = new BookRepository($registry);
        if ($genreName == '') {
            return $this->redirect('/api/v1/books');
        }

        $data = $bookRepository->findByGenre($genreName);

        if(!$data) {
            return $this->handleView($this->view("Book Genre Not Found :(", 404));
        }

        $view = $this->view($data, 200);
        return $this->handleView($view);
    }

    # ---------------------------- GET Reviews Section ---------------------------- #

    /**
     * @Rest\Get("/api/v1/reviews", name="get_reviews")
     * It returns an array of a review stored into the database
     */
    public function getReviews(ManagerRegistry $registry): Response {

        if (false === $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException();
        }

        $reviewRepository = new ReviewRepository($registry);
        $data = $reviewRepository->findAll();
        $view = $this->view($data, 200);
        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/api/v1/review/{id}", name="get_review")
     * It returns an array of a review stored into the database
     */
    public function getReview(int $id, ManagerRegistry $registry): Response {

        $reviewRepository = new ReviewRepository($registry);
        $data = $reviewRepository->find($id);

        if(!$data) {
            return $this->handleView($this->view("Review Not Found :(", 404));
        }

        $view = $this->view($data, 200);
        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/api/v1/user/{id}/reviews", name="get_user_reviews")
     * It returns an array of reviews of a user stored into the database
     */
    public function getUserReviews(int $id, ManagerRegistry $registry): Response {

        $userRepository = new UserRepository($registry);
        $data = $userRepository->find($id);

        if(!$data) {
            return $this->handleView($this->view("User Not Found :(", 404));
        }

        $view = $this->view($data->getReviews(), 200);
        return $this->handleView($view);
    }

    # ---------------------------- PUT Reviews Section ---------------------------- #

    /**
     * @Rest\Put("/api/v1/review/{id}", name="put_reviews")
     * It edits the body text of a review
     */
    public function putReview(int $id, Request $request, ManagerRegistry $registry): Response {

        $entityManager = $this->getDoctrine()->getManager();
        $reviewRepository = new ReviewRepository($registry);
        $review = $reviewRepository->find($id);

        //If the review with the passed ID doesn't exist display error message and status code
        if(!$review) {

            return $this->handleView($this->view('Review Not Found :(', 404));
        }

        $data =  $request->getContent();

        //Modify review body text and update the database
        $review->setReview($data);
        $review->setDate(date('d/m/Y'));
        $entityManager->persist($review);
        $entityManager->flush();


        //Create a directory to view the edited review
        $reviewPath = '/api/v1/review/' . $id;

        return $this->handleView($this->view('Access newly modified review: ' . $reviewPath, 200));

    }

    # ---------------------------- DELETE Reviews Section ---------------------------- #

    /**
     * @Rest\Delete ("/api/v1/review/{id}", name="delete_review")
     * It deletes a review
     */
    public function deleteReview(int $id, ManagerRegistry $registry): Response {
        $reviewRepository = new ReviewRepository($registry);
        $entityManager = $this->getDoctrine()->getManager();

        // Find review
        $review = $reviewRepository->find($id);

        //If the review with the passed ID doesn't exist display error message and status code
        if(!$review) {
            return $this->handleView($this->view('Review Not Found', 404));
        }

        //Delete review
        $entityManager->remove($review);
        $entityManager->flush();

        return $this->handleView($this->view('Review has been deleted successfully :)',200));
    }

    # ---------------------------- POST Reviews & Book Section ---------------------------- #

    /**
     * @Rest\Post ("/api/v1", name="review/{bookID}")
     * Create a new review
     */
    public function postReview(int $bookID, Request $request, ManagerRegistry $registry): Response {
        $entityManager = $this->getDoctrine()->getManager();

        $form = $this->createForm(ReviewType::class, null, [
            'csrf_protection' => false,
        ]);

        $form->submit($request->request->all());

        if ($form->isValid() && $form->isSubmitted()) {
            $bookRepository = new BookRepository($registry);
            $book = $bookRepository->find($bookID);

            if($book) {
                $userRepository = new UserRepository($registry);
                $user = $userRepository->find(3);
                $review = new Review();
                $review->setReview($form->get('Review')->getData());
                $review->setDate(date('d/m/Y'));
                $review->setCreator($user);
                $review->setBook($book);
                $entityManager->persist($review);
                $entityManager->flush();

                //Create a directory to view the new review
                $reviewPath = '/api/v1/review/' . $review->getId();
            }
            else {
                return $this->handleView($this->view('Book Not Found :(', 404));
            }
        }
        else {
            return $this->handleView($this->view('Invalid Data Posted', 400));
        }

        return $this->handleView($this->view('View the new review at: ' . $reviewPath, 201));
    }

    /**
     * @Rest\Post ("/api/v1/book/{id}", name="post_book")
     * Create a new Book
     */
    public function postBook(Request $request, ManagerRegistry $registry): Response {
        $entityManager = $this->getDoctrine()->getManager();

        $form = $this->createForm(BookType::class, null, [
            'csrf_protection' => false,
        ]);

        $form->submit($request->request->all());

        if ($form->isValid() && $form->isSubmitted()) {
            $book = new Book();


            $entityManager->persist($book);
            $entityManager->flush();

            //Create a directory to view the new review
            $reviewPath = '/api/v1/book/' . $book->getId();
        }

        else {
            return $this->handleView($this->view('Invalid Data Posted', 400));
        }

        return $this->handleView($this->view('View the new book at: ' . $reviewPath, 201));
    }

    /**
     * @Rest\Get("/oauth/v2", name="token")
     * Authorises user
     */
    public function authoriseUser(): Response {

        $clientManager = $this->container->get('fos_oauth_server.client_manager.default');
        $client = $clientManager->createClient();
        $client->setRedirectUris(array('http://127.0.0.1:8000/api/v1/reviews'));
        $client->setAllowedGrantTypes(array('token', 'authorization_code', 'password'));
        $clientManager->updateClient($client);

        return $this->redirect($this->generateUrl('fos_oauth_server_authorize', array(
            'client_id'     => $client->getPublicId(),
            'redirect_uri'  => 'http://127.0.0.1:8000/api/v1/reviews',
            'response_type' => 200
        )));

    }

}


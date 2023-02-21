<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\BookRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

class NYTimesController extends AbstractController
{

    /**
     * @Route("/nytimesbooks", name="")
     * Home page and allow searching in NY Times book catalog
     */
    public function index(Request $request): Response
    {
        $defaultData = ['book' => 'Search NY Times Reviewed Books'];
        $form = $this->createFormBuilder($defaultData)
            ->add('title', TextType::class)
            ->add('author', TextType::class, ['required' => false])
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            return $this->redirectToRoute('searchNYTimesBook', [
                'title' => $data['title'], 'author' => $data['author']
            ]);
        }

        return $this->render('ny_times/index.html.twig', [
            'controller_name' => 'NYTimesController',
            'form' => $form->createView()
        ]);
    }


    /**
     * @Route("/nytimesbooks", name="search/{title}/{author}")
     * @throws GuzzleException
     * Shows all the result of the search request retrieved from NY Times API
     */
    public function searchBook(string $title, string $author): Response
    {
        if ($title == ''){
            return $this->redirectToRoute('NYTimesHome');
        }

        $client = new Client();
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $res = $client->request('GET', 'https://api.nytimes.com/svc/books/v3/reviews.json', [
        'query' => ['api-key' => getenv('NYTIMES_API_KEY'), 'title' => $title, 'author' => $author]]);

        $books = json_decode($res->getBody(), true);

        return $this->render('ny_times/search.html.twig', [
            'controller_name' => 'NYTimesController',
            'books' => $books['results'],
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/nytimesbooks/review", name="add/{author}/{title}")
     * @throws GuzzleException
     * Shows selected book information and allows user to submit review
     */
    public function reviewNYTimesBook(String $author, String $title, Request $request, ManagerRegistry $registry): Response
    {
        if ($author == '' || $title == ''){
            return $this->redirectToRoute('NYTimesHome');
        }

        //Create connection to database
        $entityManager = $this->getDoctrine()->getManager();
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);
        $client = new Client();
        $res = $client->request('GET', 'https://api.nytimes.com/svc/books/v3/reviews.json', [
            'query' => ['api-key' => getenv('NYTIMES_API_KEY'), 'author' => $author, 'title' => $title ]]);

        $book = json_decode($res->getBody(), true)['results'][0];
        $bookList = json_decode($res->getBody(), true);

        if ($form->isSubmitted() && $form->isValid()) {
            $bookRepository = new BookRepository($registry);
            if(!$bookRepository->findByTitle($title) || !$bookRepository->findByAuthor($author)) {

                $newBook = new Book();
                $newBook
                    ->setTitle($book['book_title'])
                    ->setAuthor($book['book_author'])
                    ->setSummary($book['summary'])
                    ->setPages(500)
                    ->setGenre('Misc');

                $entityManager->persist($newBook);
            }
            else {
                $newBook = $bookRepository->findByTitle($title);
            }

            $user = $this->getUser();
            $data = $form->getData();

            $review
                ->setReview($data->getReview())
                ->setDate(date('d/m/Y'))
                ->setBook($newBook);
            $user->addReview($review);

            //Add the new Review object inside the database
            $entityManager->persist($review);
            $entityManager->flush();

            return $this->redirectToRoute('reviewNYTimesBooksSuccess');

        }
        return $this->renderForm('ny_times/review.html.twig', [
            'book' => $book,
            'bookList' => $bookList['results'],
            'form' => $form,
        ]);
    }

    /**
     * @Route("/nytimesbooks/review/", name="success")
     * Display success message when creating a new review from a NY Times Book
     */
    public function reviewSuccess(): Response {

        return $this->render('ny_times/success/success.html.twig');
    }
}

<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Review;
use App\Form\BookType;
use App\Form\ReviewType;
use App\Repository\BookRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\GeneralForm;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

class MainController extends AbstractController
{
    /*
      * @Route("/", name="home")
      */
    public function homeResponse(): Response
    {
        //Doesn't let banned user log in
        if($this->getUser()){
            if($this->getUser()->getStatus() == 0 ) {
                return $this->redirectToRoute('logout');
            }
        }

        //Pick a book based on a randomly generated ID
        $randomID = rand(1,5);
        $entityManager = $this->getDoctrine()->getManager();
        $review = $entityManager->getRepository(Review::class)->find(5);

        if (!$review) {
            throw $this->createNotFoundException(
                'No review found for id '. $randomID
            );
        }

        $reviewer = $review->getCreator();
        $book = $review->getBook();

        //Render the form on the home page
        return $this->renderForm('Default/home.html.twig', [
            'user' => $this->getUser(),
            'reviewer' => $reviewer,
            'review' => $review,
            'book' => $book,
        ]);
    }

    /*
     * @Route("/add", name="book")
     */
    public function addBookResponse(SluggerInterface $slugger,Request $request): Response
    {
        //Instantiate a new Book object
        $book = new Book();

        if(!$this->getUser()) {
            return $this->redirectToRoute('home');
        }

        //Create book form
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //Start a Doctrine connection to the database
            $entityManager = $this->getDoctrine()->getManager();

            //Retrieve data from the form
            $data = $form->getData();
            $bookFile = $form->get('image')->getData();

            if ($bookFile) {
                $originalFilename = pathinfo($bookFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $bookFile->guessExtension();
                // Move the file to the directory where brochures are stored

                try {
                    $bookFile->move(
                        $this->getParameter('book_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
            }

            //Set the book object the new value from the form
            $book
                ->setTitle($data->getTitle())
                ->setAuthor($data->getAuthor())
                ->setPages($data->getPages())
                ->setSummary($data->getSummary())
                ->setGenre($data->getGenre())
                ->setImage($newFilename)
                ;


            //Add the new Book object inside the database
            $entityManager->persist($book);
            $entityManager->flush();

            //Go to homepage after adding the new object in the database
            return $this->redirectToRoute('home');
        }

        //Loads & render add book webpage with form
        return $this->renderForm('add/book.html.twig', [
            'form' => $form,
        ]);
    }

    /*
     * @Route("/add", name="findBook")
     */
    public function findBookResponse(Request $request): Response
    {

        if(!$this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $defaultData = ['book' => 'Find Book'];
        $form = $this->createFormBuilder($defaultData)
            ->add('bookname', TextType::class)
            ->add('send', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            return $this->redirectToRoute('addReview', [
                'bookname' => $data['bookname'],
            ]);
        }


        //Loads & render add review webpage
        return $this->renderForm('add/findBook.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/getBookHint", name="{l}", methods="GET")
     */
    public function searchBook(ManagerRegistry $registry, string $l): Response
    {
        $bookRepository = new BookRepository($registry);
        $books =  $bookRepository->findBooksByString($l);

        if(!$books) {
            return new Response('No Books Found');
        }

        return new Response(json_encode($books));
    }

    /**
     * @Route("/add", name="review/{bookname}")
     */
    public function addReview(ManagerRegistry $registry, Request $request, string $bookname): Response
    {
        //Create connection to database
        $entityManager = $this->getDoctrine()->getManager();

        if(!$this->getUser()) {
            return $this->redirectToRoute('home');
        }

        //Create review form
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        $bookRepository = new BookRepository($registry);
        $id = $bookRepository->findIDByName($bookname);

        // Get Book entity of book user wants to write the review on
        $book = $entityManager->getRepository(Book::class)->find($id);

        if ($form->isSubmitted() && $form->isValid()) {

            //Get current user information
            $user = $this->getUser();
            $data = $form->getData();

            //Set the review object the new value from the form

            $review
                ->setReview($data->getReview())
                ->setBook($book)
                ->setDate(date('d/m/Y'));
            $user->addReview($review);

            //Add the new Review object inside the database
            $entityManager->persist($review);
            $entityManager->flush();

            //Go to homepage after adding the new object in the database
            return $this->redirectToRoute('home');
        }

        //Loads & render add review webpage
        return $this->render('add/add-review.html.twig', [
            'bookname' => $bookname,
            'id' => $id,
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit", name="editReview/{id}")
     */
    public function editReview(ManagerRegistry $registry,Request $request, int $id): Response
    {
        //Create connection to database
        $entityManager = $this->getDoctrine()->getManager();

        //Retrieve current user
        $user = $this->getUser();

        //Find the review based on the ID passed on the parameter
        $reviewRepository = new ReviewRepository($registry);
        $review = $reviewRepository->findUserReview($user->getID(),$id);

        //If you can't find it display this
        if (!$review) {
            throw $this->createNotFoundException(
                'No review found for id '.$id
            );
        }

        // Retrieve information about the book reviews
        $book = $review->getBook();

        //Create review form
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $review
                ->setReview($data->getReview())
                ->setDate(date('d/m/Y'));
            $entityManager->flush();

            return $this->redirectToRoute('editSuccess', [
                'id' => $review->getId()
            ]);
        }

        return $this->render('edit/editReview.html.twig', [
            'review' => $review,
            'form' => $form->createView(),
            'book' => $book,
        ]);
    }

    /**
     * @Route("/getReview", name="{l}", methods="GET")
     */
    public function searchReview(ManagerRegistry $registry, int $l): Response
    {
        $reviewRepository = new ReviewRepository($registry);
        $review =  $reviewRepository->findRandomReview($l);

        if(!$review) {
            return new Response('No Reviews Found');
        }

        return new Response(json_encode($review));
    }

    /**
     * @Route("/", name="profile")
     */
    public function profileResponse(ManagerRegistry $registry): Response {

        if(!$this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $reviewRepository = new ReviewRepository($registry);
        $user = $this->getUser();
        $myReviews = $reviewRepository->findUserReviews($user->getId());

        return $this->render('Default/profile.html.twig' , [
            'user' => $user,
            'myReviews' => $myReviews,
        ]);
    }

    /**
     * @Route("/success", name="editReview/{id}")
     */
    public function editSuccess($id): Response
    {

        //Create connection to database
        $entityManager = $this->getDoctrine()->getManager();

        //Find the review based on the ID passed on the parameter
        $review = $entityManager->getRepository(Review::class)->find($id);


        return $this->render('success/editSuccess.html.twig', [
            'review' => $review,
        ]);
    }

    /**
     * @Route("/admin", name="")
     */
    public function adminResponse(ManagerRegistry $registry): Response {

        $reviewRepository = new ReviewRepository($registry);
        $myReviews = $reviewRepository->findAll();

        return $this->render('Default/admin.html.twig', [
            'allReview' => $myReviews,
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/deleteReview", name="{id}")
     */
    public function deleteReview(ManagerRegistry $registry, string $id): Response {

        //Create connection to database
        $entityManager = $this->getDoctrine()->getManager();

        $reviewRepository = new ReviewRepository($registry);
        $review = $reviewRepository->find($id);

        $entityManager->remove($review);
        $entityManager->flush();

        return $this->redirectToRoute('admin');
    }

    /**
     * @Route("/deleteUser", name="{id}")
     */
    public function deleteUser(ManagerRegistry $registry, string $id): Response {

        //Create connection to database
        $entityManager = $this->getDoctrine()->getManager();

        $userRepository = new UserRepository($registry);
        $user = $userRepository->find($id);

        $user
            ->setStatus(false);

        //Add the new Review object inside the database
        $entityManager->persist($user);
        $entityManager->flush();


        return $this->redirectToRoute('admin');
    }

    /**
     * @Route("/activateUser", name="{id}")
     */
    public function activateUser(ManagerRegistry $registry, string $id): Response {

        //Create connection to database
        $entityManager = $this->getDoctrine()->getManager();

        $userRepository = new UserRepository($registry);
        $user = $userRepository->find($id);

        $user
            ->setStatus(true);

        //Add the new Review object inside the database
        $entityManager->persist($user);
        $entityManager->flush();


        return $this->redirectToRoute('admin');
    }
}

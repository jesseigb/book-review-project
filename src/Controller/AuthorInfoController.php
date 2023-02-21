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

class AuthorInfoController extends AbstractController
{

    /**
     * @Route("/author", name="info/{name}")
     * Shows more info about an author using two API, one for a picture and one for the info
     * Also shows all the book by him store in the database
     * @throws GuzzleException
     */
    public function index(string $name, Request $request, ManagerRegistry $registry): Response
    {
        if ($name == ''){
            return $this->redirectToRoute('NYTimesHome');
        }

        // Retrieve All Books available in the database about the author
        $bookRepository = new BookRepository($registry);
        $books = $bookRepository->findByAuthor($name);

        //Retrieve picture of author from API
        $client = new Client();
        $photoRes = $client->request('GET', 'https://pixabay.com/api/', [
            'query' => ['key' => getenv('PIXABAY_API_KEY'), 'q' => $name,
                'image_type' => 'photo', 'orientation' => 'vertical'], 'safesearch' => 'true']);

        $photo = json_decode($photoRes->getBody(), true)['hits'];

        //If there is no picture returned, send a default No Image picture
        if(!$photo) {
            $photo = 'noimage.jpg';
        }
        else{
            $photo = $photo[0];
        }

        //Retrieve information about author from API
        $infoRes = $client->request('GET', 'https://api.api-ninjas.com/v1/celebrity',
            ['query' => ['name' => $name],
                'headers' => ['X-Api-Key' => getenv('NINJA_API_KEY')]]);

        $info = json_decode($infoRes->getBody(), true);

        //If there is no author found returned, send a default a text
        if(!$info) {
            $info = 'No Author Found';
        }
        else{
            $info = $info[0];
        }


        return $this->render('Default/author.html.twig', [
            'name' => $name,
            'books' => $books,
            'photo' => $photo,
            'info' => $info
        ]);
    }



}

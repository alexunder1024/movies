<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\MovieFormType;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MoviesController extends AbstractController
{
    private $em;
    private $movieRepository;
    private $filesystem;
    public function __construct(MovieRepository $movieRepository, EntityManagerInterface $em, Filesystem $filesystem)
    {
        $this->movieRepository = $movieRepository;
        $this->em = $em;
        $this->filesystem = $filesystem;
    }
//    #[Route('/movies/{name}', name: 'movies', defaults: ['name' => null], methods: ['GET', 'HEAD'])]
//    public function index($name): Response
//    {
//        $movies = ['Avengers', 'Loki', 'Inception', 'Avatar'];
//        return $this->render('movies/index.html.twig', [
//            'controller_name' => 'MoviesController',
//            'name' => $name,
//            'movies' => $movies,
//        ]);
//    }

//    private $em;
//    public function __construct(EntityManagerInterface $em)
//    {
//        $this->em = $em;
//    }

    #[Route('/movies', name: 'movies', methods: ['GET'])]
    public function index(): Response
    {

        //Sql
        //findAll() - SELECT * FROM movies;
        //find() - SELECT * from movies WHERE id = 1;
        //findBy() - SELECT * FROM movies ORDER BY id DESC;
        //findBy() - SELECT * from movies WHERE id = 1 AND title = 'The Dark Knight'
        //count() - SELECT COUNT(id) FROM movies

        //Commands
        //$movies = $this->movieRepository->findAll();
        //$movies = $this->movieRepository->find(1);
        //$movies = $this->movieRepository->findBy([], ['id' => 'DESC']);
        //$movies = $this->movieRepository->findOneBy(['id' => 1, 'title' => 'The Dark Knight', []]);
        //$movies = $this->movieRepository->count([]);

//        $repository = $this->em->getRepository(Movie::class);
//        $movies = $repository->findAll();
//        dd($movies);
        $movies = $this->movieRepository->findAll();

        return $this->render('movies/index.html.twig', [
            'movies' => $movies
        ]);
    }

    #[Route('/movies/create', name: 'create_movie', priority: 10)]
    public function create(Request $request): Response
    {
        $movie = new Movie();
        $form = $this->createForm(MovieFormType::class, $movie);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newMovie = $form->getData();

            $imagePath = $form->get('imagePath')->getData();
            if ($imagePath) {
                $newFileName = uniqid() . '.' . $imagePath->guessExtension();

                try{
                    $imagePath->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads',
                        $newFileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $newMovie->setImagePath('/uploads/' . $newFileName);
            }

            $this->em->persist($newMovie);
            $this->em->flush();

            return $this->redirectToRoute('movies');
        }

        return $this->render('movies/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/movies/{id}', name: 'show_movie', methods: ['GET'])]
    public function show($id): Response
    {
        $movie = $this->movieRepository->find($id);

        return $this->render('movies/show.html.twig', [
            'movie' => $movie
        ]);
    }

    #[Route('/movies/edit/{id}', name: 'edit_movie')]
    public function edit($id, Request $request): Response
    {
        $movie = $this->movieRepository->find($id);
        $form = $this->createForm(MovieFormType::class, $movie);

        $form->handleRequest($request);
        $imagePath = $form->get('imagePath')->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            if ($imagePath) {
                if ($movie->getImagePath() !== null) {
                        $this->filesystem->remove($this->getParameter('kernel.project_dir') . '/public' . $movie->getImagePath());
                        $this->getParameter('kernel.project_dir') . $movie->getImagePath();
                        $newFileName = uniqid() . '.' . $imagePath->guessExtension();

                        try{
                            $imagePath->move(
                                $this->getParameter('kernel.project_dir') . '/public/uploads',
                                $newFileName
                            );
                        } catch (FileException $e) {
                            return new Response($e->getMessage());
                        }

                        $movie->setImagePath('/uploads/' . $newFileName);
                        $this->em->flush();

                        return $this->redirectToRoute('movies');
//                    }
                }
            } else {
                $movie->setTitle($form->get('title')->getData());
                $movie->setReleaseYear($form->get('releaseYear')->getData());
                $movie->setDescription($form->get('description')->getData());

                $this->em->flush();
                return $this->redirectToRoute('movies');
            }
        }

        return $this->render('movies/edit.html.twig', [
            'movie' => $movie,
            'form' => $form->createView()
        ]);
    }

    #[Route('/movies/delete/{id}', name: 'delete_movie', methods: ['GET', 'DELETE'])]
    public function delete($id): Response
    {
        $movie = $this->movieRepository->find($id);

        $this->filesystem->remove($this->getParameter('kernel.project_dir') . '/public' . $movie->getImagePath());

        $this->em->remove($movie);
        $this->em->flush();

        return $this->redirectToRoute('movies');
    }

}

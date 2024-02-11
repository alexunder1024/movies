<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\CommentFormType;
use App\Form\MovieFormType;
use App\Repository\CommentsRepository;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MoviesController extends AbstractController
{
    public function __construct(
        private MovieRepository $movieRepository,
        private EntityManagerInterface $em,
        private Filesystem $filesystem
    )
    {

    }

    #[Route('/movies', name: 'movies', methods: ['GET'])]
    public function index(): Response
    {
        $movies = $this->movieRepository->findAll();

        return $this->render('movies/index.html.twig', [
            'movies' => $movies
        ]);
    }

    #[Route('/movies/create', name: 'create_movie')]
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
                    $imagePath->move($this->getParameter('kernel.project_dir') . '/public/uploads', $newFileName);
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

    #[Route('/movies/{id}', name: 'show_movie')]
    public function show(Request $request, int $id): Response
    {
        $movie = $this->movieRepository->find($id);

        $form = $this->createForm(CommentFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            return new JsonResponse(['success' => true]);
        }

        return $this->render('movies/show.html.twig', [
            'movie' => $movie,
            'form' => $form->createView(),
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

        $comments = $movie->getComments();
        foreach ($comments as $comment) {
            $this->em->remove($comment);
        }

        $this->em->remove($movie);
        $this->em->flush();

        return $this->redirectToRoute('movies');
    }
}
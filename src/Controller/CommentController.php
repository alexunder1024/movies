<?php

namespace App\Controller;

use App\Entity\Comments;
use App\Entity\Movie;
use App\Form\CommentFormType;
use App\Repository\CommentsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends AbstractController
{
    public function __construct(private CommentsRepository $commentsRepository, private EntityManagerInterface $em)
    {
    }

    #[Route('/movies/{id}/comment/{commentId}/edit', name: 'edit-comment')]
    public function editComment(Request $request, int $commentId): Response
    {
        $comment = $this->commentsRepository->find($commentId);

        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $data = $form->get('text')->getData();
            return new JsonResponse($data);
        }

        return $this->render('movies/comment.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/movies/{id}/add-comment', name: 'add-comment')]
    public function addComment(Request $request, int $id): Response
    {
        $movie = $this->em->getRepository(Movie::class)->find($id);
        $author = $this->getUser();

        $comment = new Comments();
        $comment->setMovie($movie);
        $comment->setAuthor($author);
        $comment->setDate(new \DateTime(date("Y-m-d H:i:s")));

        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($comment);
            $this->em->flush();

//            return new JsonResponse(['id' => $comment->getId(), 'text' => $comment->getText(), 'author' => $comment->getAuthor()->getEmail()]);

            $comments = [[
                'id' => $comment->getId(),
                'email' => $comment->getAuthor()->getEmail(),
                'isEditable' => true,
                'text' => $comment->getText()
            ]];

            return $this->render('movies/show_comments.html.twig', [
                'comments' => $comments,
            ]);
        }

        return $this->render('movies/comment.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/movies/delete-comment/{id}', name: 'delete-comment', methods: ['DELETE'])]
    public function deleteComment(Request $request, int $id): Response
    {
        $comment = $this->commentsRepository->find($id);

        if (!$comment) {
            throw $this->createNotFoundException('Коментар не знайдено');
        }

        if($this->getUser()->getEmail() === $comment->getAuthor()->getEmail() || $this->isGranted('ROLE_ADMIN', $this->getUser())) {
            $this->em->remove($comment);
            $this->em->flush();

            return new JsonResponse(['success']);
        }

        return new JsonResponse(['false']);

    }
}
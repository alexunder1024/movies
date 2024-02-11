<?php

namespace App\Controller;

use App\Repository\CommentsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShowCommentsController extends AbstractController
{
    public function __construct(
        private CommentsRepository $commentRepository
    )
    {
    }

    #[Route('/show-comments/{id}', name: 'comment')]
    public function showComments(int $id): Response
    {
        $comments = $this->commentRepository->getComments($id);

        if ($this->getUser()) {
            $userEmail = $this->getUser()->getUserIdentifier();

            for ($i = 0; $i < count($comments); $i++) {
                $comments[$i]['email'] === $userEmail ? $comments[$i]['isEditable'] = true : $comments[$i]['isEditable'] = false;
            }
        }

        return $this->render('movies/show_comments.html.twig', [
            'comments' => $comments,
        ]);
    }
}
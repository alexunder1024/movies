<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LuckyController extends AbstractController
{
    #[Route('/posts/{id<\d+>?5}', name: 'post_show')]
    public function showPost(int $id): Response
    {
        return $this->render('lucky/number.html.twig', [
            'number' => $id,
        ]);
    }
}
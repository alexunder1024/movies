<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    #[Route('/blog/{page}', name: 'blog_list', requirements: ['page' => '\d+'])]
    public function list(int $page): Response
    {
        return $this->render('a');
    }

    #[Route('/blog/{slug}', name: 'blog_show')]
    public function show($slug): Response
    {
        return $this->render('a');
    }
}
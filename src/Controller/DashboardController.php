<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{

    #[Route('/' , name: 'app_dashboard_index')]
    public function index(Request $request): Response
    {
        return $this->render('dashboard/index.html.twig');
    }
}

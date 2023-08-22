<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
* @Route("/api/user", name="app_user")
*/
class HomeController extends AbstractController
{
    /**
     * @Route("/info", name="info", methods={"GET"})
     */
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json(['user' => $user], Response::HTTP_OK, [], ['groups' => 'user_info']);
    }
}

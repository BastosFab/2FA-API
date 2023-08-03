<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Token;
use App\Form\LoginType;
use App\Repository\UserRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormBuilderInterface;


 /**
 * @Route("/api", name="app_login_page")
 */
class LoginController extends AbstractController
{
    
    /**
     * @Route("/login", name="app_login", methods={"POST"})
     */
    public function index(
        Request $request, 
        UserRepository $userRepository, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        dd($data);
        $form = $this->createForm(LoginType::class);
        $form->submit($data);

        if(!$form->isValid()) throw new BadRequestHttpException('Invalid Form');

        $user = $userRepository->findOneBy(['matricule' => $form->get('matricule')->getData()]);
        if (!$user) throw new BadRequestHttpException('Invalid credentials');

        if (!$passwordHasher->isPasswordValid($user, $form->get('password')->getData())) throw new BadRequestHttpException('Invalid credentials');

        $token = new Token();
        $token->setUser($user);

        $entityManager->persist($token);
        $entityManager->flush();

        return $this->json($token, Response::HTTP_OK, []);
    }

    /**
     * @Route("/user", name="app_user", methods={"GET"})
     */
    public function getUserInfo(VehiculeRepository $vehiculeRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $vehicules = $vehiculeRepository->getVehiculeOwnTasks($user->getId());
        return $this->json(
            ['user' => $user, 'vehicules' => $vehicules], Response::HTTP_OK, [], ['groups' => ['user_info']]
        );
    }
}

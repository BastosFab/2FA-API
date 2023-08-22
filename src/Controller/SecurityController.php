<?php

namespace App\Controller;

use App\Form\TwoFactorAuthenticatorType;
use App\Services\TokenService;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Token;
use App\Form\LoginType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Form\RegisterType;


 /**
 * @Route("/api", name="app_security")
 */
class SecurityController extends AbstractController
{

    /**
     * @Route("/register", name="_register", methods={"POST"})
     */
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, GoogleAuthenticatorInterface $googleAuthenticator): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = new User();

        $form = $this->createForm(RegisterType::class, $user);
        $form->submit($data);

        $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);
        
        
        $secret = $googleAuthenticator->generateSecret();
        $user->setGoogleAuthenticatorSecret($secret);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['user' => $user, 'Qrcode Url' => $googleAuthenticator->getQRContent($user)], Response::HTTP_CREATED, [], ["groups" => ['user_info']]);
    }

    /**
     * @Route("/login", name="_login", methods={"POST"})
     * @throws \Exception
     */
    public function login(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        $form = $this->createForm(LoginType::class);
        $form->submit($data);
        if(!$form->isValid()) throw new BadRequestHttpException('Invalid Form');

        $user = $userRepository->findOneBy(['email' => $form->getData()['email']]);
        if (!$user) throw new BadRequestHttpException('User not found');
        if (!$passwordHasher->isPasswordValid($user, $form->getData()['password'])) throw new BadRequestHttpException('Invalid password');

        $token = (new Token())
            ->setUser($user)
            ->setToken(TokenService::generateToken())
            ->setNeedTOTPVerification($user->isGoogleAuthenticatorEnabled());

        $entityManager->persist($token);
        $entityManager->flush();

        return $this->json(['user' => $user, 'token' => $token], Response::HTTP_OK, [], ["groups" => ['user_info', 'token_info']]);
    }

    /**
     * @Route("/2FA", name="_2FA", methods={"POST"})
     */
    public function twoFactorAuthenticator(Request $request, GoogleAuthenticatorInterface $googleAuthenticator, TokenService $tokenService, EntityManagerInterface $entityManager): Response
    {
        /* @Var User $user */
        $user = $this->getUser();
        if (!$user) throw new BadRequestHttpException('User not found');
        $form = $this->createForm(TwoFactorAuthenticatorType::class);
        $form->submit(json_decode($request->getContent(), true));

        if(!$googleAuthenticator->checkCode($user, $form->getData()['code'])) throw new BadRequestHttpException('Invalid code');

        $tokenService->token->setNeedTOTPVerification(false);

        $entityManager->persist($tokenService->token);
        $entityManager->flush();

        return $this->json(['message: connexion ok !'], Response::HTTP_OK);
    }
}

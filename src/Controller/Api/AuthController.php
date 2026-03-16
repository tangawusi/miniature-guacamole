<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserRepository $users,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'A valid email is required.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (mb_strlen($password) < 8) {
            return $this->json(['message' => 'Password must be at least 8 characters long.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($users->findOneBy(['email' => $email]) !== null) {
            return $this->json(['message' => 'Email is already registered.'], JsonResponse::HTTP_CONFLICT);
        }

        $user = (new User())
            ->setEmail($email)
            ->setPassword($passwordHasher->hashPassword(new User(), $password))
            ->setVerificationToken($emailVerificationService->generateToken())
            ->setIsVerified(false);

        $entityManager->persist($user);
        $entityManager->flush();

        $emailFile = $emailVerificationService->persistVerificationEmail($user);

        return $this->json([
            'message' => 'Registration successful. Confirm your account using the email persisted in var/emails.',
            'emailFile' => str_replace($this->getParameter('kernel.project_dir') . '/', '', $emailFile),
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/verify/{token}', name: 'api_verify_account', methods: ['GET'])]
    public function verify(string $token, UserRepository $users, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $users->findOneByVerificationToken($token);

        if ($user === null) {
            return $this->json(['message' => 'Verification token is invalid.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $user->setIsVerified(true)
            ->setVerificationToken(null);

        $entityManager->flush();

        return $this->json(['message' => 'Account verified successfully.']);
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('This should be handled by the firewall.');
    }

    #[Route('/status', name: 'api_auth_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['authenticated' => false]);
        }

        return $this->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'isVerified' => $user->isVerified(),
            ],
        ]);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new \LogicException('This should be handled by the firewall.');
    }
}

<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route("/api")]
final class AuthController extends AbstractController
{
    #[Route("/register", name: "api_register", methods: ["POST"])]
   public function register(
       Request $request,
       UserPasswordHasherInterface $passwordHasher,
       EntityManagerInterface $entityManager,
       SerializerInterface $serializer,
       ValidatorInterface $validator
   ) : JsonResponse
   {
       $data = json_decode($request->getContent(), true);

       $user = new User();
       $user->setEmail($data['email'] ?? '');
       $user->setFirstName($data['firstName'] ?? '');
       $user->setLastName($data['lastName'] ?? '');

       // Validate user entity
       $errors = $validator->validate($user);
       dump($errors);
       if (count($errors) > 0) {
           $errorMessages = [];
           foreach ($errors as $error) {
               $errorMessages[$error->getPropertyPath()] = $error->getMessage();
           }

           return $this->json([
               'errors' => $errorMessages,
           ], Response::HTTP_BAD_REQUEST);
       }

       // Check if email already exists
       $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
       if ($existingUser) {
           return $this->json(['error' => 'Email already exists'], Response::HTTP_CONFLICT);
       }

       $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
       $user->setPassword($hashedPassword);

       // Save the user
       $entityManager->persist($user);
       $entityManager->flush();

       return $this->json([
           'message' => 'User registered successfully',
           'user'    => [
               'id'         => $user->getId(),
               'email'      => $user->getEmail(),
               'firstName'  => $user->getFirstName(),
               'lastName'   => $user->getLastName(),
           ]
       ], Response::HTTP_CREATED);
   }

   #[Route("/login", name: "api_login", methods: ["POST"])]
   public function login(
       Request $request,
       UserPasswordHasherInterface $passwordHasher,
       JWTTokenManagerInterface $jwtManager,
       EntityManagerInterface $entityManager,
       UserProviderInterface $userProvider
   ) : JsonResponse
   {
        $data = json_decode($request->getContent(), true);

        // validate input
       if(empty($data['email']) || empty($data['password'])) {
           return $this->json([
               'error' => 'Email and Password are required'
           ], Response::HTTP_BAD_REQUEST);
       }

       try {
           // Find User by eMail
           $user = $entityManager->getRepository(User::class)->findOneBy([
               'email' => $data['email'],
           ]);

           // Check if user exists
           if(!$user) {
               return $this->json([
                   'error' => 'Invalid credentials'
               ], Response::HTTP_UNAUTHORIZED);
           }

           // Verify password
           if(!$passwordHasher->isPasswordValid($user, $data['password'])) {
               return $this->json([
                   'error' => 'Password is wrong'
               ], Response::HTTP_UNAUTHORIZED);
           }

           // Generate Token
           $token = $jwtManager->create($user);

           return $this->json([
               'token' => $token,
               'message' => 'User logged in successfully',
               'user'    => [
                   'id'          => $user->getId(),
                   'email'       => $user->getEmail(),
                   'firstName'   => $user->getFirstName(),
                   'lastName'    => $user->getLastName(),
                   'roles'       => $user->getRoles()
           ]
           ]);
       } catch(AuthenticationException $e) {
           return $this->json(['error' => 'Authentication failed '. $e->getMessage()], Response::HTTP_UNAUTHORIZED);
       }
   }

    #[Route('/profile', name: 'api_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        // This endpoint requires authentication, so we can get the user from the token
        $user = $this->getUser();

//        return $this->json([
//            'user' => [
//                'id' => $user->getId(),
//                'email' => $user->getEmail(),
//                'firstName' => $user->getFirstName(),
//                'lastName' => $user->getLastName(),
//                'roles' => $user->getRoles(),
//            ]
//        ]);
        return $this->json([
            'user' => [
                'id'         => $user ? $user->getId() : null,
                'email'      => $user->getEmail(),
                'firstName'  => $user->getFirstName(),
                'lastName'   => $user->getLastName(),
                'roles'      => $user->getRoles(),
            ],
        ]);
    }


}

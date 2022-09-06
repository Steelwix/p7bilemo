<?php

namespace App\Controller;

use App\Entity\Clients;
use App\Entity\Users;
use App\Repository\ClientsRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'app_users', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function getAllUsers(UsersRepository $usersRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $userList = $usersRepository->findAllWithPagination($page, $limit);
        $jsonUserList = $serializer->serialize($userList, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);


        // return new JsonResponse($jsonPhonesList, Response::HTTP_OK, [], true);

        //$page = $request->get('page', 1);
        //$limit = $request->get('limit', 10);

        //$idCache = "getAllPhones-" . $page . "-" . $limit;
        //$jsonPhonesList = $cachePool->get($idCache,S function (ItemInterface $item) use ($phonesRepository, $page, $limit, $serializer) {
        //     $item->tag("getAllPhones");
        //     $phonesList = $phonesRepository->findAllWithPagination($page, $limit);
        //     return $serializer->serialize($phonesList, 'json', ['groups' => 'getPhones']);
        // });

        // return new JsonResponse($jsonPhonesList, Response::HTTP_OK, [], true);
    }
    #[Route('/api/users', name: 'app_create_user', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function createUser(ClientsRepository $clientsRepository, UserPasswordHasherInterface $userPasswordHasher, Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $newUser = $serializer->deserialize($request->getContent(), Users::class, 'json');
        $content = $request->toArray();
        $user = $this->getUser();
        $userRole = $user->getRoles();

        if ($userRole === "ROLE_SUPER_ADMIN") {
            // Récupération de l'idCLient. S'il n'est pas défini, alors on met -1 par défaut.
            $idClient = $content['idClient'] ?? -1;

            // On cherche le client qui correspond et on l'assigne au user.
            // Si "find" ne trouve pas le client, alors null sera retourné.
            $newUser->setClient($clientsRepository->find($idClient));
        } else {
            $client = $user->getClient();
            $newUser->setClient($client);
        }


        $unhashedPassword = $content['password'];
        $newUser->setPassword($userPasswordHasher->hashPassword($newUser, $unhashedPassword));

        $em->persist($newUser);
        $em->flush();

        $jsonUser = $serializer->serialize($newUser, 'json', ['groups' => 'getUsers']);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/users/{id}', name: 'app_one_user', methods: ['GET'])]
    public function getDetailUser(Users $checkingUser, SerializerInterface $serializer)
    {
        $user = $this->getUser();
        $userRole = $user->getRoles();
        $userClient = $user->getClient();
        $checkingUserClient = $checkingUser->getClient();

        if ($userRole === "ROLE_SUPER_ADMIN" || $checkingUserClient === $userClient) {

            $jsonUser = $serializer->serialize($checkingUser, 'json', ['groups' => 'getUsers']);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    #[Route('/api/users/{id}', name: 'app_one_book', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function updateUser(Users $currentUser, UserPasswordHasherInterface $userPasswordHasher, SerializerInterface $serializer, EntityManagerInterface $em, ClientsRepository $clientsRepository, Request $request)
    {
        $currentUserRole = $currentUser->getRoles();
        $currentUserClient = $currentUser->getClient();
        $user = $this->getUser();
        $userRole = $user->getRoles();
        $userClient = $user->getClient();

        if ($userRole === "ROLE_SUPER_ADMIN" || $currentUserClient === $userClient) {

            $updatedUser = $serializer->deserialize($request->getContent(), Users::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);

            $content = $request->toArray();
            if (isset($content['password'])) {
                $unhashedPassword = $content['password'];
                $updatedUser->setPassword($userPasswordHasher->hashPassword($updatedUser, $unhashedPassword));
            }
            if ($userRole === "ROLE_SUPER_ADMIN") {
                $idClient = $content['idClient'] ?? -1;

                $updatedUser->setClient($clientsRepository->find($idClient));
            } else {
                $client = $user->getClient();
                $updatedUser->setClient($client);
            }
            $em->persist($updatedUser);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    #[Route('/api/users/{id}', name: 'app_delete_user', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function deleteUser(Users $currentUser, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $userRole = $user->getRoles();
        $userClient = $user->getClient();
        $currentUserClient = $currentUser->getClient();

        if ($userRole === "ROLE_SUPER_ADMIN" || $currentUserClient === $userClient) {
            $em->remove($currentUser);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

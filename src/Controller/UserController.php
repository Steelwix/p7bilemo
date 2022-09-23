<?php

namespace App\Controller;

use App\Entity\Clients;
use App\Entity\Users;
use App\Repository\ClientsRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use JMS\Serializer\SerializerInterface as JMSSerializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @method ?Users getUser() */
class UserController extends AbstractController
{

    /**
     * 
     * Use this method to get the user list.
     *
     * @OA\Response(
     *     response=200,
     *     description="You must be SUPER ADMIN to use this method",
     *  
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Users::class, groups={"getUsers"}))
     *     )
     * )
    
     * @OA\Tag(name="Users")
     * 
     */
    #[Route('/api/users', name: 'app_users', methods: ['GET'])]
    public function getAllUsers(UsersRepository $usersRepository, JMSSerializer $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $user = $this->getUser();
        $userRole = $user->getRoles();
        if ($userRole != array("ROLE_SUPER_ADMIN")) {
            $context = SerializationContext::create()->setGroups(["getUsers"]);
            $jsonUser = $serializer->serialize($user, 'json', $context);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $idCache = "allUsersCache-" . $page . "-" . $limit;
        $jsonUsersList = $cachePool->get($idCache, function (ItemInterface $item) use ($usersRepository, $page, $limit, $serializer) {
            $item->tag("allUsersCache");
            $usersList = $usersRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(["getUsers"]);
            return $serializer->serialize($usersList, 'json', $context);
        });

        return new JsonResponse($jsonUsersList, Response::HTTP_OK, [], true);
    }
    /**
     * Use this method to create a user.
     *
     * @OA\Response(
     *     response=200,
     *     description="Username will be used for login, as password. The password will be hashed. The client will be the same as yours.
      For the roles, you can create a simple user with ''roles '': [''ROLE_USER''] or an admin with  ''roles '': [''ROLE_ADMIN'']. For SUPER ADMIN,
      use the id client to define the new user client. you can also define a new super admin with  ''roles '': [''ROLE_SUPER_ADMIN''] ",
     *  
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Users::class, groups={"createUser"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="username",
     *     in="query",
     *     description="username for your user",
     *     @OA\Schema(type="string")
     * )
     *     @OA\Parameter(
     *     name="password",
     *     in="query",
     *     description="user password",
     *     @OA\Schema(type="string")
     * )
     *     @OA\Parameter(
     *     name="email",
     *     in="query",
     *     description="user's email",
     *     @OA\Schema(type="string")
     * )
     *      @OA\Parameter(
     *     name="client",
     *     in="query",
     *     description="client id assigned to the user",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Users")
     *
     */
    #[Route('/api/users', name: 'app_create_user', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function createUser(
        ClientsRepository $clientsRepository,
        UserPasswordHasherInterface $userPasswordHasher,
        Request $request,
        TagAwareCacheInterface $cache,
        JMSSerializer $jmsserializer,
        SerializerInterface $serializerInterface,
        EntityManagerInterface $em
    ): JsonResponse {
        $cache->invalidateTags(["allUsersCache"]);
        $content = $request->toArray();
        $user = $this->getUser();
        $userRole = $user->getRoles();
        $newUser = $serializerInterface->deserialize($request->getContent(), Users::class, 'json');


        if ($userRole == array("ROLE_SUPER_ADMIN")) {
            // Récupération de l'idCLient. S'il n'est pas défini, alors on met -1 par défaut.
            $idClient = $content['Client'] ?? -1;
            // On cherche le client qui correspond et on l'assigne au user.
            // Si "find" ne trouve pas le client, alors null sera retourné.

            $newUser->setClient($clientsRepository->findOneById($idClient));
        } else {
            $client = $user->getClient();
            $newUser->setClient($client);
            if ($content['roles'] == array("ROLE_SUPER_ADMIN")) {
                $content['roles'] = null;
            }
        }

        $unhashedPassword = $content['password'];
        $newUser->setPassword($userPasswordHasher->hashPassword($newUser, $unhashedPassword));
        $em->persist($newUser);
        $em->flush();
        $context = SerializationContext::create()->setGroups(["getUsers"]);
        $jsonUser = $jmsserializer->serialize($newUser, 'json', $context);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }
    /**
     * 
     * Use this method to get details about one user.
     *
     * @OA\Response(
     *     response=200,
     *     description="Use this method to get details about one user. If you are not SUPER_ADMIN, you can get infos about a user that is not assigned to the same client as yours",
     *  
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Users::class, groups={"getUsers"}))
     *     )
     * )
    
     * @OA\Tag(name="Users")
     * 
     */
    #[Route('/api/users/{id}', name: 'app_one_user', methods: ['GET'])]
    public function getDetailUser(Users $checkingUser, JMSSerializer $serializer)
    {
        $user = $this->getUser();
        $userRole = $user->getRoles();
        $userClient = $user->getClient();
        $checkingUserClient = $checkingUser->getClient();

        if ($userRole == array("ROLE_SUPER_ADMIN") || $checkingUserClient === $userClient) {
            $context = SerializationContext::create()->setGroups(["getUsers"]);
            $jsonUser = $serializer->serialize($checkingUser, 'json', $context);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * 
     * Use this method to modify a user.
     *
     * @OA\Response(
     *     response=200,
     *     description="Username will be used for login, as password. The password will be hashed. The client will be the same as yours.
      For the roles, you can create a simple user with ''roles '': [''ROLE_USER''] or an admin with  ''roles '': [''ROLE_ADMIN'']. For SUPER ADMIN,
      use the id client to define the new user client. you can also define a new super admin with  ''roles '': [''ROLE_SUPER_ADMIN'']",
     *  
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Users::class, groups={"createUser"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="username",
     *     in="query",
     *     description="username for your user",
     *     @OA\Schema(type="string")
     * )
     *     @OA\Parameter(
     *     name="password",
     *     in="query",
     *     description="user password",
     *     @OA\Schema(type="string")
     * )
     *     @OA\Parameter(
     *     name="email",
     *     in="query",
     *     description="user's email",
     *     @OA\Schema(type="string")
     * )
     *      @OA\Parameter(
     *     name="client",
     *     in="query",
     *     description="client id assigned to the user",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Users")
     * 
     */
    #[Route('/api/users/{id}', name: 'app_update_user', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function updateUser(
        Users $currentUser,
        UserPasswordHasherInterface $userPasswordHasher,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache,
        EntityManagerInterface $em,
        ClientsRepository $clientsRepository,
        Request $request,
        ValidatorInterface $validator
    ) {
        $cache->invalidateTags(["allUsersCache"]);
        $currentUserRole = $currentUser->getRoles();
        $currentUserClient = $currentUser->getClient();
        $user = $this->getUser();
        $userRole = $user->getRoles();
        $userClient = $user->getClient();
        if ($currentUserRole == array("ROLE_SUPER_ADMIN") && $userRole != array("ROLE_SUPER_ADMIN")) {
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        }
        if ($userRole == array("ROLE_SUPER_ADMIN") || $currentUserClient === $userClient) {

            $updatedUser = $serializer->deserialize($request->getContent(), Users::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);

            $content = $request->toArray();
            if (isset($content['password'])) {
                $unhashedPassword = $content['password'];
                $updatedUser->setPassword($userPasswordHasher->hashPassword($updatedUser, $unhashedPassword));
            }
            if ($userRole == array("ROLE_SUPER_ADMIN")) {
                $idClient = $content['Client'] ?? -1;

                $updatedUser->setClient($clientsRepository->find($idClient));
            } else {
                $client = $user->getClient();
                $updatedUser->setClient($client);
            }
            $errors = $validator->validate($updatedUser);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $em->persist($updatedUser);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    /**
     * 
     * Use this method to delete a user.
     *
     * @OA\Response(
     *     response=200,
     *     description="If you are not SUPER ADMIN, you can only delete users assigned to the same client as yours",
     *  
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Users::class, groups={"createUser"}))
     *     )
     * )
    
     * @OA\Tag(name="Users")
     * 
     */
    #[Route('/api/users/{id}', name: 'app_delete_user', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function deleteUser(
        Users $currentUser,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $cache->invalidateTags(["allUsersCache"]);
        $user = $this->getUser();
        $userRole = $user->getRoles();
        $userClient = $user->getClient();
        $currentUserRole = $currentUser->getRoles();
        $currentUserClient = $currentUser->getClient();
        if ($currentUserRole == array("ROLE_SUPER_ADMIN") && $userRole != array("ROLE_SUPER_ADMIN")) {
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        }
        if ($userRole == array("ROLE_SUPER_ADMIN") || $currentUserClient === $userClient) {
            $em->remove($currentUser);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

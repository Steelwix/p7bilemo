<?php

namespace App\Controller;

use App\Entity\Clients;
use App\Repository\ClientsRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

/**
 * @method ?User getUser()
 */
class ClientController extends AbstractController
{
    /**
     * Use this method to get the client list.
     *
     * @OA\Response(
     *     response=200,
     *     description=" This method gives you info about your client entity. For SUPER ADMIN, it returns all clients",
     *  
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Clients::class, groups={"getClients"}))
     *     )
     * )
    
     * @OA\Tag(name="Clients")
     *
     */
    #[Route('/api/clients', name: 'app_clients', methods: ['GET'])]
    public function getAllClients(ClientsRepository $clientsRepository, JMSSerializer $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $user = $this->getUser();
        $userRole = $user->getRoles();
        if ($userRole !== array("ROLE_SUPER_ADMIN")) {

            $idCache = "allClientsCache-";
            $jsonClientList = $cachePool->get($idCache, function (ItemInterface $item) use ($clientsRepository, $serializer) {
                $item->tag("allClientsCache");
                $user = $this->getUser();
                $userClient = $user->getClient();
                $idClient = $userClient->getId();
                $clientList = $clientsRepository->findOneById($idClient);
                $context = SerializationContext::create()->setGroups(["getClients"]);
                return $serializer->serialize($clientList, 'json', $context);
            });
            return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
        } else {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);
            $idCache = "allClientsCache-" . $page . "-" . $limit;
            $jsonClientList = $cachePool->get($idCache, function (ItemInterface $item) use ($clientsRepository, $page, $limit, $serializer) {
                $item->tag("allClientsCache");
                $clientList = $clientsRepository->findAllWithPagination($page, $limit);
                $context = SerializationContext::create()->setGroups(["getClients"]);
                return $serializer->serialize($clientList, 'json', $context);
            });

            return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
        }
    }
    /**
     * Use this method to create a client.
     *
     * @OA\Response(
     *     response=200,
     *     description=" You must be SUPER ADMIN to use this method",
     *  
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Clients::class, groups={"getClients"}))
     *     )
     * )
                @OA\Parameter(
     *     name="name",
     *     in="query",
     *     description="client's name",
     *     @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Clients")
     *
     */
    #[Route('/api/clients', name: 'app_create_client', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function createPhone(
        Request $request,
        SerializerInterface $serializer,
        JMSSerializer $jmsserializer,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache,
        ValidatorInterface $validator
    ): JsonResponse {
        $cache->invalidateTags(["allClientsCache"]);
        $client = $serializer->deserialize($request->getContent(), Clients::class, 'json');
        $errors = $validator->validate($client);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $em->persist($client);
        $em->flush();
        $context = SerializationContext::create()->setGroups(["getClients"]);
        $jsonClient = $jmsserializer->serialize($client, 'json', $context);

        return new JsonResponse($jsonClient, Response::HTTP_CREATED, [], true);
    }
    /**
     * Use this method to get the detail about one client.
     *
     * @OA\Response(
     *     response=200,
     *     description=" This method gives you info about your client entity. For SUPER ADMIN, it returns the concerned client",
     *  
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Clients::class, groups={"getClients"}))
     *     )
     * )
    
     * @OA\Tag(name="Clients")
     *
     */
    #[Route('/api/clients/{id}', name: 'app_one_client', methods: ['GET'])]
    public function getDetailClient(Clients $client, JMSSerializer $serializer)
    {

        $user = $this->getUser();
        $userRole = $user->getRoles();
        $userClient = $user->getClient();

        if ($userRole !== array("ROLE_SUPER_ADMIN")) {
            $context = SerializationContext::create()->setGroups(["getClients"]);
            $jsonClientList = $serializer->serialize($userClient, 'json',  $context);
            return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
        } else {
            $context = SerializationContext::create()->setGroups(["getClients"]);
            $jsonClientList = $serializer->serialize($client, 'json',  $context);
            return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
        }
    }
    /**
     * Use this method to modify the infos about one client.
     *
     * @OA\Response(
     *     response=200,
     *     description=" You must be SUPER ADMIN to use this method",
     *  
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Clients::class, groups={"getClients"}))
     *     )
     * )
            @OA\Parameter(
     *     name="name",
     *     in="query",
     *     description="client's name",
     *     @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Clients")
     *
     */
    #[Route('/api/clients/{id}', name: 'app_update_client', methods: ['PUT'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function UpdatePhone(
        Request $request,
        SerializerInterface $serializer,
        JMSSerializer $jmsserializer,
        Clients $currentClient,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache,
        ValidatorInterface $validator
    ) {
        $cache->invalidateTags(["allClientsCache"]);
        $updatedClient = $serializer->deserialize($request->getContent(), Clients::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentClient]);
        $errors = $validator->validate($updatedClient);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $em->persist($updatedClient);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    /**
     * Use this method to delete one client.
     *
     * @OA\Response(
     *     response=200,
     *     description=" You must be SUPER ADMIN to use this method",
     *  
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Clients::class, groups={"getClients"}))
     *     )
     * )
    
     * @OA\Tag(name="Clients")
     *
     */
    #[Route('/api/clients/{id}', name: 'app_delete_client', methods: ['DELETE'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function deletePhone(
        Clients $client,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $cache->invalidateTags(["allClientsCache"]);
        $em->remove($client);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    /**
     * Use this method to get all users from one client.
     *
     * @OA\Response(
     *     response=200,
     *     description=" This method will returns you all users from your client entity. For SUPER ADMIN, it returns the user list from the concerned client",
     *  
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Clients::class, groups={"getClientUsers"}))
     *     )
     * )
    
     * @OA\Tag(name="Clients")
     *
     */
    #[Route('/api/clients/{id}/users', name: 'app_users_from_client', methods: ['GET'])]
    public function getAllUsersFromClient(Clients $client, UsersRepository $usersRepository, TagAwareCacheInterface $cachePool, Request $request,  JMSSerializer $serializer)
    {

        $user = $this->getUser();
        $userRole = $user->getRoles();
        $userClient = $user->getClient();
        if ($userRole !== array("ROLE_SUPER_ADMIN") &&  $userClient !== $client) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        if ($userRole !== array("ROLE_SUPER_ADMIN") &&  $userClient === $client) {

            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);
            $idCache = "clientAllUsers-" . $page . "-" . $limit;
            $jsonClientList = $cachePool->get($idCache, function (ItemInterface $item) use ($usersRepository, $page, $limit, $serializer) {
                $item->tag("clientAllUsers");
                $usersList = $usersRepository->findAllWithPagination($page, $limit);
                $user = $this->getUser();
                $userClient = $user->getClient()->getId();
                foreach ($usersList as $index => $userList) {
                    $userListClient = $userList->getClient()->getId();
                    if ($userListClient !== $userClient) {
                        unset($usersList[$index]);
                    }
                }
                $context = SerializationContext::create()->setGroups(["getClientUsers"]);
                return $serializer->serialize($usersList, 'json', $context);
            });

            return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
        }
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $idCache = "clientAllUsers-" . $page . "-" . $limit;
        $jsonClientList = $cachePool->get($idCache, function (ItemInterface $item) use ($usersRepository, $page, $limit, $serializer) {
            $item->tag("clientAllUsers");
            $usersList = $usersRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(["getClientUsers"]);
            return $serializer->serialize($usersList, 'json', $context);
        });

        return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
    }
}

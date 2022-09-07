<?php

namespace App\Controller;

use App\Entity\Clients;
use App\Repository\ClientsRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use JMS\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @method ?User getUser()
 */
class ClientController extends AbstractController
{
    #[Route('/api/clients', name: 'app_clients', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function getAllBooks(ClientsRepository $clientsRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $user = $this->getUser();

        $userRole = $user->getRoles();

        if ($userRole !== "ROLE_SUPER_ADMIN") {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);
            $idCache = "allClientsCache-" . $page . "-" . $limit;
            $jsonClientList = $cachePool->get($idCache, function (ItemInterface $item) use ($clientsRepository, $page, $limit, $serializer) {
                $item->tag("allClientsCache");
                $user = $this->getUser();
                $userClient = $user->getClient();
                $context = SerializationContext::create()->setGroups(["getClients"]);
                return $serializer->serialize($userClient, 'json', $context);
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
    #[Route('/api/clients', name: 'app_create_client', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function createPhone(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $cache->invalidateTags(["allClientsCache"]);
        $client = $serializer->deserialize($request->getContent(), Clients::class, 'json');
        $em->persist($client);
        $em->flush();
        $context = SerializationContext::create()->setGroups(["getClients"]);
        $jsonClient = $serializer->serialize($client, 'json', $context);

        return new JsonResponse($jsonClient, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/clients/{id}', name: 'app_one_client', methods: ['GET'])]
    public function getDetailClient(Clients $client, SerializerInterface $serializer)
    {

        $user = $this->getUser();
        $userRole = $user->getRoles();
        $userClient = $user->getClient();

        if ($userRole !== "ROLE_SUPER_ADMIN" || $userClient !== $client) {
            $context = SerializationContext::create()->setGroups(["getClients"]);
            $jsonClientList = $serializer->serialize($userClient, 'json',  $context);
            return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
        } else {
            $context = SerializationContext::create()->setGroups(["getClients"]);
            $jsonClient = $serializer->serialize($client, 'json',  $context);
            return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
        }
    }
    #[Route('/api/clients/{id}', name: 'app_update_client', methods: ['PUT'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function UpdatePhone(
        Request $request,
        SerializerInterface $serializer,
        Clients $currentClient,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ) {
        $cache->invalidateTags(["allClientsCache"]);
        $updatedClient = $serializer->deserialize($request->getContent(), Clients::class, 'json');
        $currentClient->setName($updatedClient->setName());
        $em->persist($currentClient);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
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
    #[Route('/api/clients/{id}/users', name: 'app_users_from_client', methods: ['GET'])]
    public function getAllUsersFromClient(Clients $client, SerializerInterface $serializer)
    {
        $user = $this->getUser();
        $userRole = $user->getRoles();
        $userClient = $user->getClient();

        if ($userRole !== "ROLE_SUPER_ADMIN" && $userClient !== $client) {
            $context = SerializationContext::create()->setGroups(["getClients"]);
            $jsonUserClient = $serializer->serialize($userClient, 'json', $context);
            return new JsonResponse($jsonUserClient, Response::HTTP_OK, [], true);
        } else {
            $context = SerializationContext::create()->setGroups(["getClients"]);
            $jsonClient = $serializer->serialize($client, 'json', $context);
            return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
        }
    }
}

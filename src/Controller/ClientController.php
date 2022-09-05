<?php

namespace App\Controller;

use App\Entity\Clients;
use App\Repository\ClientsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ClientController extends AbstractController
{
    #[Route('/api/clients', name: 'app_clients', methods: ['GET'])]
    public function getAllBooks(ClientsRepository $clientsRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $clientList = $clientsRepository->findAllWithPagination($page, $limit);
        $jsonClientList = $serializer->serialize($clientList, 'json', ['groups' => 'getClients']);
        return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);


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
    #[Route('/api/clients', name: 'app_create_client', methods: ['POST'])]
    public function createPhone(Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $client = $serializer->deserialize($request->getContent(), Clients::class, 'json');
        $em->persist($client);
        $em->flush();

        $jsonClient = $serializer->serialize($client, 'json', ['groups' => 'getClients']);

        return new JsonResponse($jsonClient, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/clients/{id}', name: 'app_one_client', methods: ['GET'])]
    public function getDetailClient(Clients $client, SerializerInterface $serializer)
    {
        $jsonClient = $serializer->serialize($client, 'json');
        return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
    }
    #[Route('/api/clients/{id}', name: 'app_update_client', methods: ['PUT'])]
    public function UpdatePhone(
        Request $request,
        SerializerInterface $serializer,
        Clients $currentClient,
        EntityManagerInterface $em
    ) {
        $updatedClient = $serializer->deserialize($request->getContent(), Clients::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentClient]);
        $em->persist($updatedClient);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    #[Route('/api/clients/{id}', name: 'app_delete_client', methods: ['DELETE'])]
    public function deletePhone(Clients $client, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($client);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

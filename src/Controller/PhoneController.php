<?php

namespace App\Controller;

use App\Entity\Phones;
use App\Repository\PhonesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class PhoneController extends AbstractController
{
    #[Route('/api/phones', name: 'app_phones', methods: ['GET'])]
    public function getAllBooks(PhonesRepository $phonesRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $phoneList = $phonesRepository->findAllWithPagination($page, $limit);
        $jsonPhoneList = $serializer->serialize($phoneList, 'json');
        return new JsonResponse($jsonPhoneList, Response::HTTP_OK, [], true);


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

    #[Route('/api/phones/{id}', name: 'app_one_phone', methods: ['GET'])]
    public function getDetailPhone(Phones $phone, SerializerInterface $serializer)
    {
        $jsonPhone = $serializer->serialize($phone, 'json');
        return new JsonResponse($jsonPhone, Response::HTTP_OK, [], true);
    }
}

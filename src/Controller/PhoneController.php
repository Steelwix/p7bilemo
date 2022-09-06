<?php

namespace App\Controller;

use App\Entity\Phones;
use App\Repository\PhonesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
    #[Route('/api/phones', name: 'app_create_phone', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function createPhone(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        $phone = $serializer->deserialize($request->getContent(), Phones::class, 'json');
        $em->persist($phone);
        $em->flush();

        $jsonPhone = $serializer->serialize($phone, 'json', ['groups' => 'getPhones']);
        $location = $urlGenerator->generate('app_one_phone', ['id' => $phone->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonPhone, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/phones/{id}', name: 'app_one_phone', methods: ['GET'])]
    public function getDetailPhone(Phones $phone, SerializerInterface $serializer)
    {
        $jsonPhone = $serializer->serialize($phone, 'json');
        return new JsonResponse($jsonPhone, Response::HTTP_OK, [], true);
    }
    #[Route('/api/phones/{id}', name: 'app_update_phone', methods: ['PUT'])]
    public function updatePhone(
        Request $request,
        SerializerInterface $serializer,
        Phones $currentPhone,
        EntityManagerInterface $em
    ) {
        $updatedPhone = $serializer->deserialize($request->getContent(), Phones::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentPhone]);
        $em->persist($updatedPhone);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    #[Route('/api/phones/{id}', name: 'app_delete_phone', methods: ['DELETE'])]
    public function deletePhone(Phones $phone, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($phone);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

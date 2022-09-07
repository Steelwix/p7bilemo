<?php

namespace App\Controller;

use App\Entity\Phones;
use App\Repository\PhonesRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class PhoneController extends AbstractController
{
    #[Route('/api/phones', name: 'app_phones', methods: ['GET'])]
    public function getAllBooks(PhonesRepository $phonesRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $idCache = "allPhonesCache-" . $page . "-" . $limit;
        $jsonPhonesList = $cachePool->get($idCache, function (ItemInterface $item) use ($phonesRepository, $page, $limit, $serializer) {
            $item->tag("allPhonesCache");
            $phonesList = $phonesRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(["getPhones"]);
            return $serializer->serialize($phonesList, 'json', $context);
        });

        return new JsonResponse($jsonPhonesList, Response::HTTP_OK, [], true);
    }
    #[Route('/api/phones', name: 'app_create_phone', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function createPhone(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        TagAwareCacheInterface $cache,
        ValidatorInterface $validator
    ): JsonResponse {
        $cache->invalidateTags(["allPhonesCache"]);
        $phone = $serializer->deserialize($request->getContent(), Phones::class, 'json');
        $errors = $validator->validate($phone);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($phone);
        $em->flush();
        $context = SerializationContext::create()->setGroups(["getPhones"]);
        $jsonPhone = $serializer->serialize($phone, 'json', $context);
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
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function updatePhone(
        Request $request,
        SerializerInterface $serializer,
        Phones $currentPhone,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ) {
        $cache->invalidateTags(["allPhonesCache"]);
        $updatedPhone = $serializer->deserialize($request->getContent(), Phones::class, 'json');
        $currentPhone->setName($updatedPhone->getName());
        $currentPhone->setPrice($updatedPhone->setPrice());
        $currentPhone->setDescription($updatedPhone->setDescription());
        $em->persist($updatedPhone);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    #[Route('/api/phones/{id}', name: 'app_delete_phone', methods: ['DELETE'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function deletePhone(
        Phones $phone,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $cache->invalidateTags(["allPhonesCache"]);
        $em->remove($phone);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

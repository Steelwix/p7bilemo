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
use JMS\Serializer\SerializerInterface as JMSSerializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class PhoneController extends AbstractController
{
    /**
     * Use this method to get all phones.
     *
     * @OA\Response(
     *     response=200,
     *     description="Use this method to get all phones.",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phones::class, groups={"getPhones"}))
     *     )
     * )
    
     * @OA\Tag(name="Phones")
     *
     */
    #[Route('/api/phones', name: 'app_phones', methods: ['GET'])]
    public function getAllPhones(PhonesRepository $phonesRepository, JMSSerializer $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
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

    /**
     * Use this method create a phone.
     *
     * @OA\Response(
     *     response=200,
     *     description="You must be SUPER ADMIN to use this method.",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phones::class, groups={"getPhones"}))
     *     )
     * )
    
     * @OA\Tag(name="Phones")
     *
     */
    #[Route('/api/phones', name: 'app_create_phone', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function createPhone(
        Request $request,
        SerializerInterface $serializer,
        JMSSerializer $jmserializer,
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
        $jsonPhone = $jmserializer->serialize($phone, 'json', $context);
        $location = $urlGenerator->generate('app_one_phone', ['id' => $phone->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonPhone, Response::HTTP_CREATED, ["Location" => $location], true);
    }
    /**
     * Use this method to get details about one phone.
     *
     * @OA\Response(
     *     response=200,
     *     description=" Use this method to get details about one phone.",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phones::class, groups={"getPhones"}))
     *     )
     * )
    
     * @OA\Tag(name="Phones")
     *
     */
    #[Route('/api/phones/{id}', name: 'app_one_phone', methods: ['GET'])]
    public function getDetailPhone(Phones $phone, JMSSerializer $serializer)
    {
        $jsonPhone = $serializer->serialize($phone, 'json');
        return new JsonResponse($jsonPhone, Response::HTTP_OK, [], true);
    }
    /**
     * Use this method to modify the infos about one phone.
     *
     * @OA\Response(
     *     response=200,
     *     description=" You must be SUPER ADMIN to use this method.",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phones::class, groups={"getPhones"}))
     *     )
     * )
    
     * @OA\Tag(name="Phones")
     *
     */
    #[Route('/api/phones/{id}', name: 'app_update_phone', methods: ['PUT'])]
    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour intéragir avec cette route')]
    public function updatePhone(
        Request $request,
        SerializerInterface $serializer,
        Phones $currentPhone,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache,
        ValidatorInterface $validator
    ) {
        $cache->invalidateTags(["allPhonesCache"]);
        $updatedPhone = $serializer->deserialize($request->getContent(), Phones::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentPhone]);
        $errors = $validator->validate($updatedPhone);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($updatedPhone);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    /**
     * Use this method to delete one phone.
     *
     * @OA\Response(
     *     response=200,
     *     description=" You must be SUPER ADMIN to use this method.",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phones::class, groups={"getPhones"}))
     *     )
     * )
    
     * @OA\Tag(name="Phones")
     *
     */
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

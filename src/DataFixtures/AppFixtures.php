<?php

namespace App\DataFixtures;

use App\Entity\Clients;
use App\Entity\Phones;
use App\Entity\Users;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        //phones
        $phone = new Phones;
        $phone->setName("ONE PLUS NORD 2 5G");
        $phone->setDescription("Meilleur rapport qualité-prix du marché");
        $phone->setPrice('398');
        $manager->persist($phone);

        $phone = new Phones;
        $phone->setName("IPHONE 14");
        $phone->setDescription("Le nouveau mastodonte du marché");
        $phone->setPrice('1399');
        $manager->persist($phone);

        $phone = new Phones;
        $phone->setName("SAMSUNG GALAXY A53");
        $phone->setDescription("Le meilleur investissement de Samsung");
        $phone->setPrice('379');
        $manager->persist($phone);

        $phone = new Phones;
        $phone->setName("OPPO FIND X PRO");
        $phone->setDescription("Le meilleur smartphone de 2022");
        $phone->setPrice('1099');
        $manager->persist($phone);

        $phone = new Phones;
        $phone->setName("XIAMOI REDMI NOTE 11");
        $phone->setDescription("Le petit prix incontournable");
        $phone->setPrice('199');
        $manager->persist($phone);

        $phone = new Phones;
        $phone->setName("IPHONE 13 PRO MAX");
        $phone->setDescription("plus gros, plus fort, plus polyvalent");
        $phone->setPrice('1259');
        $manager->persist($phone);

        //clients
        $client = new Clients;
        $client->setName("Bilemo");
        $manager->persist($client);
        $listClient[] = $client;

        $client = new Clients;
        $client->setName("Orange");
        $manager->persist($client);
        $listClient[] = $client;

        $client = new Clients;
        $client->setName("SFR");
        $manager->persist($client);
        $listClient[] = $client;

        //users

        $user = new Users;
        $user->setClient($listClient[array_rand($listClient)]);
        $user->setEmail("mhunmael@hotmail.com");
        $user->setRoles(["ROLE_SUPER_ADMIN"]);
        $user->setUsername("Steelwix");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "motdepasse"));
        $manager->persist($user);

        $user = new Users;
        $user->setClient($listClient[array_rand($listClient)]);
        $user->setEmail("orange@gmail.com");
        $user->setRoles(["ROLE_ADMIN"]);
        $user->setUsername("AdminOrange");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "motdepasse"));
        $manager->persist($user);

        $user = new Users;
        $user->setClient($listClient[array_rand($listClient)]);
        $user->setEmail("virgil@hotmail.com");
        $user->setRoles(["ROLE_USER"]);
        $user->setUsername("Virgil");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "devimaycry"));
        $manager->persist($user);

        $user = new Users;
        $user->setClient($listClient[array_rand($listClient)]);
        $user->setEmail("dante@hotmail.com");
        $user->setRoles(["ROLE_USER"]);
        $user->setUsername("Dante");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "devilmaycry"));
        $manager->persist($user);

        $user = new Users;
        $user->setClient($listClient[array_rand($listClient)]);
        $user->setEmail("thomas@hotmail.com");
        $user->setRoles(["ROLE_USER"]);
        $user->setUsername("Thomas");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "motdepasse"));
        $manager->persist($user);


        $manager->flush();
    }
}

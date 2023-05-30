<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
class UserFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
     {
         $this->passwordEncoder = $passwordEncoder;
     }

      public function load(ObjectManager $manager)
      {
          $user = new User();
          // ...
         $user->setUsername('leonel');
         $user->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
         $user->setPassword($this->passwordEncoder->encodePassword(
             $user,
             'nosecret'
         ));
         $manager->persist($user);
         $manager->flush();

          // ...
      }
}

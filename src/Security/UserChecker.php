<?php

namespace App\Security;

use App\Entity\User as AppUser;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\UserRepository;

class UserChecker implements UserCheckerInterface
{
    private $userRepository;
    public function __construct(UserRepository $userRepository){
        $this->userRepository = $userRepository;
    }
    
    public function checkPreAuth(UserInterface $user):void
    {
        if (!$user instanceof AppUser) {
            throw new CustomUserMessageAccountStatusException('Utilisateur inexistant, <a href="https://mysoleas.com/user/register">Veuillez cliquer ici pour crée un compte</a>');
        }

        if($user->isDeleted()){
            // the message passed to this exception is meant to be displayed to the user
            throw new CustomUserMessageAccountStatusException('Your user account no longer exists.');
        }
        if(!$user->isVerified()){
            throw new CustomUserMessageAccountStatusException('Compte Inactif, <a href="https://mysoleas.com/user/resend-verification-mail">Veuillez cliquer ici pour l\'activé</a>');
        }
    }

    public function checkPostAuth(UserInterface $user):void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        // user account is expired, the user may be notified
        if (!$user->isVerified()) {
            throw new AccountExpiredException('Compte Inactif, veuillez l\'activer via votre boite mail');
            //return new RedirectResponse($this->urlGenerator->generate('leutch_resend_verification'));
        }
        $user->setLastLogin(new \DateTime('now'));
        $this->userRepository->add($user, true);
    }
}
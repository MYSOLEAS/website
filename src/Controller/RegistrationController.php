<?php

namespace App\Controller;

use App\Entity\User;
use App\Services\Notify;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    private $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    /**
     * @Route("/register/{_locale}", name="mysoleas_register", requirements={"_locale" = "en|fr"}, defaults={"_locale" ="en"})
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('mysoleas_dashboard');
        }
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('mysoleas_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('support@mysoleas.com', 'Mysoleas'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('email/registration.html.twig')
            );
            // do anything else you need here, like send an email
            $message = $translator->trans('Inscription Reussie, Veuillez consulter votre boite mail');
            $this->addFlash('success', $message);
            return $this->redirectToRoute('mysoleas_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify/email", name="mysoleas_verify_email")
     */
    public function verifyUserEmail(Request $request, UserRepository $userRepository, TokenGeneratorInterface $tokenGenerator, TranslatorInterface $translator): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('mysoleas_dashboard');
        }
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('mysoleas_register');
        }

        $user = $userRepository->findOneById($id);

        if (null === $user) {
            return $this->redirectToRoute('mysoleas_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
            $apikey = $tokenGenerator->generateToken();
            $user->setSmsCredit(50);
            $user->setSmsDeadline(new \DateTime('+30 days'));
            $user->setApikey($apikey);
            $userRepository->add($user, true);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('mysoleas_register');
        }
        $message = $translator->trans('Your email address has been verified');
        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', $message);

        return $this->redirectToRoute('mysoleas_login');
    }

    /**
     * @Route("/resend-verification-mail/{_locale}", name="mysoleas_resend_verification", requirements={"_locale" = "en|fr"}, defaults={"_locale" ="en"})
     */
    public function resendEmail(Request $request, UserRepository $userRepository, TranslatorInterface $translator){
        if ($this->getUser()) {
            return $this->redirectToRoute('mysoleas_dashboard');
        }
        if($request->isMethod('POST')){
            $email = $request->request->get('email');
        if (null === $email) {
            $message = $translator->trans('Email invalid');
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('mysoleas_register');
        }

        $user = $userRepository->findOneByEmail($email);

        if (null === $user) {
            $message = $translator->trans('Utilisateur inconnu');
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('mysoleas_register');
        }
        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation('mysoleas_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('support@mysoleas.com', 'Mysoleas'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('email/registration.html.twig')
            );
            $message = $translator->trans('Mail Renvoyé, verifier votre boite mail pour activer le compte!');
        $this->addFlash('success', $message);
        return $this->redirectToRoute('mysoleas_login');
        }
        return $this->render('security/resend_email.html.twig');
    }

    /**
     * @Route("/forgotten-password/{_locale}", name="mysoleas_forgotten_password", requirements={"_locale" = "en|fr"}, defaults={"_locale" ="en"})
     */
    public function forgottenPassword(Request $request, TokenGeneratorInterface $tokenGenerator, UrlGeneratorInterface $urlGenerator, Notify $notify, TranslatorInterface $translator):Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('mysoleas_dashboard');
        }
        if($request->isMethod('POST')){
            $email = $request->request->get('email');
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository(User::class)->findOneByEmail($email);
            // @var $user User
            if($user === null){
                $message = $translator->trans('Utilisateur inconnu');
                $this->addFlash('danger', $message);
                //$error = 'Unknow user';
                return $this->render('security/forgotten_password.html.twig', array('errorUser' => $message));
            }
            $token = $tokenGenerator->generateToken();
            try{
                $user->setResetToken($token);
                $em->flush();
            }catch(\Exception $e){
                $message = $translator->trans($e->getMessage());
                $this->addFlash('warning', $message);
            }
            $url = $this->generateUrl('mysoleas_reset_password', array('token' => $token), UrlGeneratorInterface::ABSOLUTE_URL);
            $notify->resetPassword($user, $url);
            $message = $translator->trans('Check your Mailbox to update your password');
            $this->addFlash('success', $message);
            return $this->redirectToRoute('mysoleas_login'); 
        }
        return $this->render('security/forgotten_password.html.twig');
    }

     /**
    * @Route("/reset-password/{token}/{_locale}", name="mysoleas_reset_password", requirements={"_locale" = "en|fr"}, defaults={"_locale" ="en"})
    */
    public function resetPassword(Request $request, string $token, UserPasswordEncoderInterface $passwordEncoder, Notify $notify, TranslatorInterface $translator)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('mysoleas_dashboard');
        }
        if($request->isMethod('POST')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository(User::class)->findOneByResetToken($token);
            // @var $user User
            if($user === null){
                $message = $translator->trans('Token inconnu');
                $this->addFlash('danger', $message);
                return $this->redirectToRoute('mysoleas_login');
            }
            $user->setResetToken(null);
            $user->setPassword($passwordEncoder->encodePassword($user, $request->request->get('password')));
            $em->flush();
            $notify->passwordUpdate($user);
            $message = $translator->trans('Password update successfuly');
            $this->addFlash('success', $message);
            return $this->redirectToRoute('mysoleas_login');
        }
        return $this->render('security/reset_password.html.twig', array('token' => $token));
    }

    /**
     * @Route("/v2/sms/refresh-apikey", name="mysoleas_refresh_apikey")
     */
    public function refreshApikey(Request $request, TokenGeneratorInterface $tokenGenerator, UserRepository $userRepository)
    {
        if($request->headers->get('Content-Type') == 'application/json'){
            $apikey = $request->query->get('key');
            $user = $userRepository->findOneByApikey($apikey);
            if(!$user){
                return new JsonResponse(array('success' => false, 'message' => 'Unknow user'));
            }
            $token = $tokenGenerator->generateToken();
            $user->setApikey($token);
            $userRepository->add($user, true);
            return new JsonResponse(array('success' => true, 'message' => 'apikey refresh', 'apikey' => $token));
        }
        return $this->redirectToRoute('mysoleas_sms');
    }

}

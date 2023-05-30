<?php

namespace App\Controller;

use App\Repository\CardSectionRepository;
use App\Repository\ClientRepository;
use App\Repository\PortfolioRepository;
use App\Repository\SectionRepository;
use App\Repository\SettingRepository;
use App\Repository\SmsRepository;
use App\Repository\HistoryRepository;
use App\Repository\TeamRepository;
use App\Repository\TestimonialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\History;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultController extends AbstractController
{
    /**
    * @Route("/", name="mysoleas")
    */
    public function checkLocal(Request $request)
    {
        $userLocales = $request->getlanguages();
        $acceptLocal = array('en', 'fr');
        foreach ($userLocales as $locale) {
          if(in_array($locale, $acceptLocal))
          {
            return $this->redirectToRoute('mysoleas_home', array('_locale' => $locale));
          }
        }
        return $this->redirectToRoute('mysoleas_home', array('_locale' => 'en'));
    }
    
    /**
     * @Route("/{_locale}", name="mysoleas_home", requirements={"_locale" = "en|fr"})
     */
    public function index(SettingRepository $settingRepository, ClientRepository $clientRepository, TeamRepository $teamRepository,  TestimonialRepository $testimonialRepository, PortfolioRepository $portfolioRepository, CardSectionRepository $cardSectionRepository, SectionRepository $sectionRepository, $_locale): Response
    {
        $section = $sectionRepository->findOneBy(['isProduct'=> false, 'lang' => $_locale]);
        $products = $sectionRepository->findBy(['isProduct'=> true, 'hidden' => false, 'lang' => $_locale]);
        $service = $cardSectionRepository->findOneBy(['title'=> 'Services']);
        $portfolios = $portfolioRepository->findAll();
        $testimonials = $testimonialRepository->findAll();
        $team = $teamRepository->findAll();
        $clients = $clientRepository->findAll();
        $setting = $settingRepository->findAll()[0];
        return $this->render('default/index.html.twig', [
            'section' => $section,
            'service' => $service,
            'features' => $products,
            'portfolios'=> $portfolios,
            'testimonials' => $testimonials,
            'team' => $team,
            'clients' => $clients,
            'setting' => $setting
        ]);
    }

    /**
     * @Route("/contact-us/{_locale}", name="contact", requirements={"_locale" = "en|fr"}, defaults={"_locale" ="en"})
     */
    public function contact(\Swift_Mailer $mailer, Request $request)
    {
        if($request->isMethod('POST')){   
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');
            $messageMail = (new \Swift_Message($subject.' from '.$name))->setFrom($email)
                                                                ->setTo('support@mysoleas.com')
                                                                ->setBody('<p>'.$message.'<br> send By <strong>'.$email.'</strong></p>', 'text/html', 'utf-8')
                                                                ;
            $mailer->send($messageMail);
        
            return new JsonResponse (array('success' => true, 'message' => 'Email sent succesfully, We will notify you as soon as possible. Thank You'));
        }
        return $this->redirectToRoute('mysoleas_home');  
    }

     /**
     * @Route("/user/dashboard/{_locale}", name="mysoleas_dashboard", requirements={"_locale" = "en|fr"}, defaults={"_locale" ="en"})
     */
    public function dashboard(Request $request, SmsRepository $smsRepository, HistoryRepository $historyRepository)
    {
        $listSms = $smsRepository->findBy(array('user'=>$this->getUser()), array('id'=> 'DESC'));
        $histories = $historyRepository->findBy(array('user'=>$this->getUser()), array('id'=> 'DESC'));
        return $this->render('user/index.html.twig',[
            'listSms' => $listSms,
            'histories' => $histories
        ]);
    }

    /**
     * @Route("/user/profile/{_locale}", name="mysoleas_profile", requirements={"_locale" = "en|fr"}, defaults={"_locale" ="en"})
     */
    public function profile(Request $request)
    {        
        return $this->render('user/profile.html.twig');
    }

    /**
     * @Route("/user/pack", name="mysoleas_sms_pack")
     */
    public function pack(Request $request)
    {
        return $this->render('sms/pack.html.twig');
    }

    /**
     * @Route("/user/buy-sms/{pack}/{_locale}", name="mysoleas_buy_sms", requirements={"_locale" = "en|fr", "pack" = "starter|regular|professional|ultimate"}, defaults={"_locale" ="en"})
     */
    public function buy($pack, Request $request, TranslatorInterface $translator)
    {
        if($pack == 'starter'){
            $amount = 500;
            $validity = 7;
            $quantity = 100;
        }elseif($pack == 'regular'){
            $amount = 2000;
            $validity = 30;
            $quantity = 500;
        }elseif($pack == 'professional'){
            $amount = 5000;
            $validity = 30;
            $quantity = 2000;
        }elseif($pack == 'ultimate'){
            $amount = 10000;
            $validity = 60;
            $quantity = 5000;
        }
        if($request->isMethod('post') && $request->isXmlHttpRequest()){
            $user = $this->getUser();
            if($request->request->get('amount') >= $amount){
                $history = new History();
                $history->setUser($user);
                $history->setLabel($request->request->get('description').'. ref :'.$request->request->get('ref'));
                $history->setAccount($request->request->get('amount'));
                $user->setSmsCredit($user->getSmsCredit()+$quantity);
                $deadline = new \DateTime(date("Y-m-d", strtotime($user->getSmsDeadline()->format("Y-m-d")."+ $validity day")));
                $user->setSmsDeadline($deadline);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $message = $translator->trans('activated succeccesfully. new deadline');
                $this->addFlash('success', $pack.' '.$message.' : '.$deadline->format('d/m/Y'));
                return new JsonResponse(array('success' => true, 'message' => $pack.' '.$message.' : '.$deadline->format('d/m/Y')));
            }
            $this->addFlash('danger', 'Invalid request');
            return new JsonResponse(array('success' => false, 'message' => 'Invalid request'));
        
        }
        return $this->render('sms/buy.html.twig', ['pack' => $pack, 'amount' => $amount, 'quantity' => $quantity, 'validity' => $validity]);
    }

    /**
     * @Route("/user/new/{_locale}", name="mysoleas_new_sms", requirements={"_locale" = "en|fr"}, defaults={"_locale" ="en"})
     */
    public function newSms(): Response
    {
        return $this->render('sms/new.html.twig');
    }
}

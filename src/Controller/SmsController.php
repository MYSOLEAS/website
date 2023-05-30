<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SmsRepository;
use App\Repository\UserRepository;
use App\Entity\Sms;
use App\Entity\History;
use App\Services\Operator;

/**
 * @Route("/v2/sms")
 */
class SmsController extends AbstractController
{
    use TargetPathTrait;

    /**
    * @Route("/", name="mySMS")
    */
    public function checkLocal(Request $request)
    {
        $userLocales = $request->getlanguages();
        $acceptLocal = array('en', 'fr');
        foreach ($userLocales as $locale) {
          if(in_array($locale, $acceptLocal))
          {
            return $this->redirectToRoute('mysoleas_sms', array('_locale' => $locale));
          }
        }
        return $this->redirectToRoute('mysoleas_sms', array('_locale' => 'en'));
    }
    
    /**
     * @Route("/{_locale}", name="mysoleas_sms", requirements={"_locale" = "en|fr"})
     */
    public function index(Request $request): Response
    {
        //$response->headers->setCookie(new Cookie('sosms', json_encode($data) ,time() + (7 * 24 * 60 * 60), '/',null,true,true));
        return $this->render('sms/index.html.twig');
    }

    /**
     * @Route("/add", name="mysoleas_add_sms")
     */
    public function add(Request $request, Operator $operator, UserRepository $userRepository): Response
    {
        if($request->isMethod('POST')){
            $contact = $request->request->get('contact');
            $message = $request->request->get('message');
            $apikey = $request->request->get('key');
            $totalToSend = $request->request->get('total');
            $source = $request->request->get('source');
            if(!$totalToSend){
                $totalToSend = 1;
            }
            //$referer = $request->request->get('referer');

            $user = $userRepository->findOneByApikey($apikey);
            $smsStatus = false;
            if($user){
                if($user->isSmsAllow()){
                    $smsOperator = $operator->get($contact); 
                    $sms = new Sms();
                    $sms->setPhone($contact);
                    $sms->setMessage($message);
                    $sms->setOperator($smsOperator);
                    $sms->setNotice('Message save to send list');
                    $sms->setUser($user);
                    if(strlen($contact) > 160){
                        $sms->setSendable(false);
                        $sms->setNotice('Message too long');
                    }
                    $user->setSmsCredit($user->getSmsCredit()-1);
                    $em = $this->getDoctrine()->getManager();
                    $smsStatus = true;
                    $message = 'Message save to send list';
                    $this->addFlash('success', 'Message save to send list');
                    if($user->getSmsCredit() < $totalToSend){
                        $sms->setSendable(false);
                        $sms->setNotice('Insufficient sms credit to proceed');
                        $smsStatus = false;
                        $message = 'Insufficient sms credit to proceed, please credit your account';
                        $this->addFlash('danger', 'Insufficient sms credit to proceed, please credit your account');
                    }
                    $em->persist($sms);
                    $em->persist($user);
                    $em->flush();
                }else{
                    $message = 'Contact administrator to active your apikey';
                    $this->addFlash('danger', 'Contact administrator to active your apikey');
                }
            }else{
                $message = 'Unknow user';
                $this->addFlash('danger', 'Unknow user');
            }
            if($source == 'self') {
                return $this->redirectToRoute('mysoleas_dashboard');
            }elseif($source == 'free'){
                return $this->redirectToRoute('mysoleas_sms');
                //    $response = new JsonResponse(array('success' => true, 'message'=> $message));
            //    $response->headers->setCookie(new Cookie('leutch_cart0', json_encode($data) ,time() + (7 * 24 * 60 * 60), '/',null,true,false));
                
            }else{
                return new JsonResponse(array('success' => $smsStatus, 'message' => $message));
            }
        }
        return $this->redirectToRoute('mysoleas_login');
    }

    /**
     * @Route("/send/{operator}/{action}/{uid}", name="mysoleas_send_sms")
     */
    public function send($operator, $action, $uid, Request $request, SmsRepository $smsRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        if($uid == 'mls9876SendQuickSms' && $action == 'get'){
            if($operator == 'all'){
                $smsToSend = $smsRepository->pending();
            }else{
                $smsToSend = $smsRepository->pendingByOperator($operator);
            }
            $data = [];
            foreach($smsToSend as $sms){
                $sms->setStatus(true);
                $sms->setSendAt(new \DateTime('now'));
                $sms->setNotice('Message sent');
                $em->persist($sms);
                $em->flush();
                $data[] = [$sms->getId(), $sms->getPhone(), $sms->getMessage(), time(), 0];
            }
            return new JsonResponse(array('succes' => true, 'messages' => $data, 'count' => count($data))) ;
        }
        return new JsonResponse(array('succes' => false, 'error' => 'Access interdit')) ;
        
    }
}

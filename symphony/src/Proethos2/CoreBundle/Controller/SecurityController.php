<?php

namespace Proethos2\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Proethos2\ModelBundle\Entity\User;
use Proethos2\CoreBundle\Util\Util;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login_route")
     * @Template()
     */
    public function loginAction()
    {
        $util = new Util($this->container, $this->getDoctrine());
        
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $committee_name = $util->getConfiguration("committee.name");
        $committee_description = $util->getConfiguration("committee.description");

        return array(
                'last_username' => $lastUsername,
                'error'         => $error,
                
                'committee_name' => $committee_name,
                'committee_description' => $committee_description,
            );
    }

    /**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheckAction()
    {
        // this controller will not be executed,
        // as the route is handled by the Security system
    }

    /**
     * @Route("/logout", name="logout_route")
     */
    public function logoutAction()
    {
        // this controller will not be executed,
        // as the route is handled by the Security system
    }

    /**
     * @Route("/logged", name="default_security_target")
     */
    public function loggedAction()
    {
        // if secretary, send to committee home
        $user = $this->get('security.token_storage')->getToken()->getUser();

        if(in_array('secretary', $user->getRolesSlug())) {
            return $this->redirectToRoute('crud_committee_protocol_list', array(), 301);
        }
        
        return $this->redirectToRoute('crud_investigator_protocol_list', array(), 301);
    }

    /**
     * @Route("/account/change_password", name="security_change_password")
     * @Template()
     */
    public function changePasswordAction()
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();

        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {

            // getting post data
            $post_data = $request->request->all();
            
            // checking required fields
            foreach(array('change-password', 'password-confirm') as $field) {   
                if(!isset($post_data[$field]) or empty($post_data[$field])) {
                    $session->getFlashBag()->add('error', $translator->trans("Field '$field' is required."));
                    return $this->redirectToRoute('home', array(), 301);
                }
            }

            if($post_data['change-password'] != $post_data['password-confirm']) {
                $session->getFlashBag()->add('error', $translator->trans("Passwords doesn't match."));
                return $this->redirectToRoute('home', array(), 301);
            }

            $encoderFactory = $this->get('security.encoder_factory');
            $encoder = $encoderFactory->getEncoder($user);
            $salt = $user->getSalt(); // this should be different for every user
            $password = $encoder->encodePassword($post_data['change-password'], $salt);
            $user->setPassword($password);

            if($user->getFirstAccess()) {
                $user->setFirstAccess(false);
            }

            $em->persist($user);
            $em->flush();

            $session->getFlashBag()->add('success', $translator->trans("Password changed with success."));
            return $this->redirectToRoute('login', array(), 301);

        }

        return $output;
    }

    /**
     * @Route("/public/account/forgot-my-password", name="security_forgot_my_password")
     * @Template()
     */
    public function forgotMyPasswordAction()
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();
        $util = new Util($this->container, $this->getDoctrine());

        // getting post data
        $post_data = $request->request->all();

        $user_repository = $em->getRepository('Proethos2ModelBundle:User');
        
        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {

            // getting post data
            $post_data = $request->request->all();
            
            // checking required fields
            foreach(array('email') as $field) {   
                if(!isset($post_data[$field]) or empty($post_data[$field])) {
                    $session->getFlashBag()->add('error', $translator->trans("Field '$field' is required."));
                    return $this->redirectToRoute('login', array(), 301);
                }
            }

            $user = $user_repository->findOneByEmail($post_data['email']);
            if(!$user) {
                $session->getFlashBag()->add('error', $translator->trans("Email doesn't registered in platform."));
            }

            $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();

            $hashcode = $user->generateHashcode();
            $em->persist($user);
            $em->flush();

            // TODO need to get the relative path
            $url = $baseurl . "/public/account/reset_my_password?hashcode=" . $hashcode;

            $message = \Swift_Message::newInstance()
            ->setSubject("[proethos2] " . $translator->trans("Reset your password"))
            ->setFrom($util->getConfiguration('committee.email'))
            ->setTo($post_data['email'])
            ->setBody(
                $translator->trans("Hello! You ask for a new password in Proethos2 platform.") .
                "<br>" .
                "<br>" . $translator->trans("Access the link below") . ":" .
                "<br>" .
                "<br>$url" .
                "<br>" .
                "<br>". $translator->trans("Regards") . "," .
                "<br>" . $translator->trans("Proethos2 Team")
                ,   
                'text/html'
            );
            
            $send = $this->get('mailer')->send($message);

            $session->getFlashBag()->add('success', $translator->trans("Instructions has been sended to your email."));
        }

        return $output;
    }

    /**
     * @Route("/public/account/reset_my_password", name="security_reset_my_password")
     * @Template()
     */
    public function resetMyPasswordAction()
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();

        // getting post data
        $post_data = $request->request->all();

        $user_repository = $em->getRepository('Proethos2ModelBundle:User');

        $parameters = $request->query->all();
        
        if(!isset($parameters['hashcode'])) {
            throw $this->createNotFoundException($translator->trans('Invalid hashcode'));
        }

        $hashcode = $parameters['hashcode'];
        $user = $user_repository->findOneByHashcode($hashcode);

        if(!$user) {
            throw $this->createNotFoundException($translator->trans('No user found'));
        }

        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {

            // getting post data
            $post_data = $request->request->all();
            
            // checking required fields
            foreach(array('new-password', 'confirm-password') as $field) {   
                if(!isset($post_data[$field]) or empty($post_data[$field])) {
                    $session->getFlashBag()->add('error', $translator->trans("Field '$field' is required."));
                    return $this->redirectToRoute('home', array(), 301);
                }
            }

            if($post_data['new-password'] != $post_data['confirm-password']) {
                $session->getFlashBag()->add('error', $translator->trans("Passwords doesn't match."));
                return $this->redirectToRoute('home', array(), 301);
            }

            $encoderFactory = $this->get('security.encoder_factory');
            $encoder = $encoderFactory->getEncoder($user);
            $salt = $user->getSalt(); // this should be different for every user
            $password = $encoder->encodePassword($post_data['new-password'], $salt);
            $user->setPassword($password);

            $user->cleanHashcode();

            $em->persist($user);
            $em->flush();

            $session->getFlashBag()->add('success', $translator->trans("Password changed with success."));
            return $this->redirectToRoute('home', array(), 301);

        }

        return $output;
    }

    /**
     * @Route("/public/account/new", name="security_new_user")
     * @Template()
     */
    public function newUserAction()
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();
        $util = new Util($this->container, $this->getDoctrine());

        // getting post data
        $post_data = $request->request->all();

        $user_repository = $em->getRepository('Proethos2ModelBundle:User');
        $country_repository = $em->getRepository('Proethos2ModelBundle:Country');

        $countries = $country_repository->findAll();
        $output['countries'] = $countries;
        
        $output['content'] = array();
        
        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {

            // getting post data
            $post_data = $request->request->all();
            $output['content'] = $post_data;

            // checking required fields
            foreach(array('name', 'username', 'email', 'country', 'password', 'confirm-password', 'g-recaptcha-response') as $field) {   
                if(!isset($post_data[$field]) or empty($post_data[$field])) {
                    $session->getFlashBag()->add('error', $translator->trans("Field '$field' is required."));
                    return $output;
                }
            }

            // only check captcha if not in dev
            if(strpos($_SERVER['HTTP_HOST'], 'localhost') < 0) {
                // RECAPTCHA
                $secret = $util->getConfiguration('recaptcha.secret');

                // params to send to recapctha api
                $data = array(
                    "secret" => $secret,
                    "response" => $post_data['g-recaptcha-response'],
                    "remoteip" => $_SERVER['REMOTE_ADDR'],
                );

                // options from file_Get_contents
                $options = array(
                    'http' => array(
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'POST',
                        'content' => http_build_query($data)
                    )
                );
                
                // making the POST request to API
                $context  = stream_context_create($options);
                $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify", false, $context);
                $response = json_decode($response);
                
                // if has problems, stop
                if(!$response->success) {
                    $session->getFlashBag()->add('error', $translator->trans("Have an error with captcha. Please try again."));
                    return $output;
                }
            }

            if($post_data['password'] != $post_data['confirm-password']) {
                $session->getFlashBag()->add('error', $translator->trans("Passwords doesn't match."));
                return $output;
            }

            $country = $country_repository->find($post_data['country']);

            $user = new User();
            $user->setCountry($country);
            $user->setName($post_data['name']);
            $user->setUsername($post_data['username']);
            $user->setEmail($post_data['email']);
            $user->setInstitution($post_data['institution']);
            $user->setFirstAccess(false);
            $user->setIsActive(false);

            $encoderFactory = $this->get('security.encoder_factory');
            $encoder = $encoderFactory->getEncoder($user);
            $salt = $user->getSalt(); // this should be different for every user
            $password = $encoder->encodePassword($post_data['password'], $salt);
            $user->setPassword($password);

            $user->cleanHashcode();

            $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();

            // send email to the user
            $message = \Swift_Message::newInstance()
            ->setSubject("[proethos2] " . $translator->trans("Welcome to the Proethos2 platform!"))
            ->setFrom($util->getConfiguration('committee.email'))
            ->setTo($post_data['email'])
            ->setBody(
                $translator->trans("Hello! You was registered in Proethos2 platform.") .
                "<br>" .
                "<br>" . $translator->trans("Please wait until your access was validated. We will send you an email.") .
                "<br>" .
                "<br>". $translator->trans("Regards") . "," .
                "<br>" . $translator->trans("Proethos2 Team")
                ,   
                'text/html'
            );
            $send = $this->get('mailer')->send($message);

            // send email to the secreataries
            $secretaries_emails = array();
            foreach($user_repository->findAll() as $secretary) {
                if(in_array('secretary', $secretary->getRolesSlug())) {
                    $secretaries_emails[] = $secretary->getEmail();
                }
            }

            $message = \Swift_Message::newInstance()
            ->setSubject("[proethos2] " . $translator->trans("New user on Proethos2 platform"))
            ->setFrom($util->getConfiguration('committee.email'))
            ->setTo($secretaries_emails)
            ->setBody(
                $translator->trans("Hello! There are an new user in the Proethos2 platform.") .
                "<br>" .
                "<br>" . $translator->trans("Can you check and authorize his access?") .
                "<br>" .
                "<br>" . $baseurl .
                "<br>" .
                "<br>". $translator->trans("Regards") . "," .
                "<br>" . $translator->trans("Proethos2 Team")
                ,   
                'text/html'
            );
            $send = $this->get('mailer')->send($message);

            $em->persist($user);
            $em->flush();

            $session->getFlashBag()->add('success', $translator->trans("User created with success. Wait for your approval."));
            return $this->redirectToRoute('home', array(), 301);
        }

        return $output;
    }
}
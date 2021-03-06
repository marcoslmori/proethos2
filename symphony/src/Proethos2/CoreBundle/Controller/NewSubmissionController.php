<?php

namespace Proethos2\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use Proethos2\ModelBundle\Entity\Submission;
use Proethos2\ModelBundle\Entity\SubmissionCountry;
use Proethos2\ModelBundle\Entity\SubmissionCost;
use Proethos2\ModelBundle\Entity\SubmissionTask;
use Proethos2\ModelBundle\Entity\SubmissionUpload;
use Proethos2\ModelBundle\Entity\Protocol;
use Proethos2\ModelBundle\Entity\ProtocolHistory;
use Proethos2\ModelBundle\Entity\SubmissionClinicalTrial;



class NewSubmissionController extends Controller
{
    /**
     * @Route("/submission/new/first", name="submission_new_first_step")
     * @Template()
     */
    public function FirstStepAction(Request $request)
    {
        $output = array();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();

        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {

            // getting post data
            $post_data = $request->request->all();
            
            // checking required files
            foreach(array('cientific_title', 'public_title', 'is_clinical_trial') as $field) {
                if(!isset($post_data[$field]) or empty($post_data[$field])) {
                    $session->getFlashBag()->add('error', $translator->trans("Field '$field' is required."));
                    return array();
                }
            }

            $protocol = new Protocol();
            $protocol->setOwner($user);
            $protocol->setStatus('D');
            $em->persist($protocol);
            $em->flush();

            $submission = new Submission();
            $submission->setIsClinicalTrial(($post_data['is_clinical_trial'] == 'yes') ? true : false);
            $submission->setPublicTitle($post_data['public_title']);
            $submission->setCientificTitle($post_data['cientific_title']);
            $submission->setTitleAcronyms($post_data['title_acronyms']);
            $submission->setProtocol($protocol);
            $submission->setNumber(1);

            $submission->setOwner($user);
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($submission);
            $em->flush();

            $protocol->setMainSubmission($submission);
            $em->persist($protocol);
            $em->flush();

            $session->getFlashBag()->add('success', $translator->trans("Submission started with success."));
            return $this->redirectToRoute('submission_new_second_step', array('submission_id' => $submission->getId()), 301);
        }
        
        return array();    
    }

    /**
     * @Route("/submission/new/{submission_id}/first", name="submission_new_first_created_protocol_step")
     * @Template()
     */
    public function FirstStepCreatedProtocolAction($submission_id)
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();
        
        $submission_repository = $em->getRepository('Proethos2ModelBundle:Submission');
        $user_repository = $em->getRepository('Proethos2ModelBundle:User');

        // getting the current submission
        $submission = $submission_repository->find($submission_id);
        $output['submission'] = $submission;

        if (!$submission or $submission->getIsSended()) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }

        $users = $user_repository->findAll();
        $output['users'] = $users;

        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {

            // getting post data
            $post_data = $request->request->all();
            
            // checking required files
            foreach(array('cientific_title', 'public_title', 'is_clinical_trial') as $field) {
                if(!isset($post_data[$field]) or empty($post_data[$field])) {
                    $session->getFlashBag()->add('error', $translator->trans("Field '$field' is required."));
                    return array();
                }
            }

            $submission->setIsClinicalTrial(($post_data['is_clinical_trial'] == 'yes') ? true : false);
            $submission->setPublicTitle($post_data['public_title']);
            $submission->setCientificTitle($post_data['cientific_title']);
            $submission->setTitleAcronyms($post_data['title_acronyms']);
            
            $em->persist($submission);
            $em->flush();

            $session->getFlashBag()->add('success', $translator->trans("First step saved with sucess."));
            return $this->redirectToRoute('submission_new_second_step', array('submission_id' => $submission->getId()), 301);
        }
        
        return $output;    
    }

    /**
     * @Route("/submission/new/{submission_id}/second", name="submission_new_second_step")
     * @Template()
     */
    public function SecondStepAction($submission_id)
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();
        
        $submission_repository = $em->getRepository('Proethos2ModelBundle:Submission');
        $user_repository = $em->getRepository('Proethos2ModelBundle:User');

        // getting the current submission
        $submission = $submission_repository->find($submission_id);
        $output['submission'] = $submission;

        if (!$submission or $submission->getIsSended()) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }

        $allow_to_edit_submission = true;
        $user = $this->get('security.token_storage')->getToken()->getUser();
        // if current user is not owner, check the team
        if ($user != $submission->getOwner()) {
            $allow_to_edit_submission = false;
            foreach($submission->getTeam() as $team_member) {
                // if current user = some team member, than it allows to edit  
                if ($user == $team_member) {
                    $allow_to_edit_submission = true;
                }
            }
        }
        if (!$allow_to_edit_submission) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }

        $users = $user_repository->findAll();
        $output['users'] = $users;

        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {


            // getting post data
            $post_data = $request->request->all();


            // checking required files
            $required_fields = array('abstract', 'keywords', 'introduction', 'justify', 'goals');
            foreach($required_fields as $field) {
                if(!isset($post_data[$field]) or empty($post_data[$field])) {
                    $session->getFlashBag()->add('error', $translator->trans("Field '$field' is required."));
                    return $output;
                }
            }
            
            // removing all team to readd
            foreach($submission->getTeam() as $team_user) {
                $submission->removeTeam($team_user);
            }

            // readd
            if(isset($post_data['team_user'])) {
                foreach($post_data['team_user'] as $team_user) {
                    $team_user = $user_repository->find($team_user);
                    $submission->addTeam($team_user);
                }
            }

            // new owner
            if(isset($post_data['team-new-owner'])) {
                $new_owner = $user_repository->find($team_user);
                if($new_owner) {
                    $old_owner = $submission->getOwner();
                    $submission->setOwner($new_owner);

                    $submission->removeTeam($new_owner);
                    $submission->addTeam($old_owner);
                }
            }

            // adding fields to model
            $submission->setAbstract($post_data['abstract']);
            $submission->setKeywords($post_data['keywords']);
            $submission->setIntroduction($post_data['introduction']);
            $submission->setJustification($post_data['justify']);
            $submission->setGoals($post_data['goals']);
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($submission);
            $em->flush();
            
            // if is a post to set a new owner, returns to the same page
            if(isset($post_data['team-new-owner'])) {
                return $this->redirectToRoute('submission_new_second_step', array('submission_id' => $submission->getId()), 301);
            }

            $session->getFlashBag()->add('success', $translator->trans("Second step saved with sucess."));
            return $this->redirectToRoute('submission_new_third_step', array('submission_id' => $submission->getId()), 301);
        }
        
        return $output;
    }

    /**
     * @Route("/submission/new/{submission_id}/third", name="submission_new_third_step")
     * @Template()
     */
    public function ThirdStepAction($submission_id)
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();

        $submission_repository = $em->getRepository('Proethos2ModelBundle:Submission');
        $user_repository = $em->getRepository('Proethos2ModelBundle:User');
        $submission_country_repository = $em->getRepository('Proethos2ModelBundle:SubmissionCountry');
        $gender_repository = $em->getRepository('Proethos2ModelBundle:Gender');
        $recruitment_status_repository = $em->getRepository('Proethos2ModelBundle:RecruitmentStatus');
        $country_repository = $em->getRepository('Proethos2ModelBundle:Country');

        // getting the current submission
        $submission = $submission_repository->find($submission_id);
        $output['submission'] = $submission;

        // getting gender list
        $genders = $gender_repository->findByStatus(true);
        $output['genders'] = $genders;

        // getting recruitment_statuses list
        $recruitment_statuses = $recruitment_status_repository->findByStatus(true);
        $output['recruitment_statuses'] = $recruitment_statuses;
        
        $countries = $country_repository->findAll();
        $output['countries'] = $countries;

        if (!$submission or $submission->getIsSended()) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }

        $allow_to_edit_submission = true;
        $user = $this->get('security.token_storage')->getToken()->getUser();
        // if current user is not owner, check the team
        if ($user != $submission->getOwner()) {
            $allow_to_edit_submission = false;
            foreach($submission->getTeam() as $team_member) {
                // if current user = some team member, than it allows to edit  
                if ($user == $team_member) {
                    $allow_to_edit_submission = true;
                }
            }
        }
        if (!$allow_to_edit_submission) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }

        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {

            // getting post data
            $post_data = $request->request->all();

            // checking required files
            $required_fields = array('study-design', 'gender', 'sample-size', 'minimum-age', 'maximum-age', 'inclusion-criteria', 
                'exclusion-criteria', 'recruitment-init-date', 'recruitment-status', 'interventions', 'primary-outcome');
            foreach($required_fields as $field) {
                if(!isset($post_data[$field]) or empty($post_data[$field])) {
                    $session->getFlashBag()->add('error', $translator->trans("Field '$field' is required."));
                    return $output;
                }
            }

            $recruitment_init_date = new \DateTime($post_data['recruitment-init-date']);
            if(new \DateTime('NOW') > $recruitment_init_date) {
                $session->getFlashBag()->add('error', $translator->trans("The recruitment init date needs to be greater than today."));
                return $output;
            }

            // adding fields to model
            $submission->setStudyDesign($post_data['study-design']);
            $submission->setHealthCondition($post_data['health-condition']);
            $submission->setSampleSize($post_data['sample-size']);
            $submission->setMinimumAge($post_data['minimum-age']);
            $submission->setMaximumAge($post_data['maximum-age']);
            $submission->setInclusionCriteria($post_data['inclusion-criteria']);
            $submission->setExclusionCriteria($post_data['exclusion-criteria']);
            $submission->setRecruitmentInitDate(new \DateTime($post_data['recruitment-init-date']));

            // gender
            $selected_gender = $gender_repository->find($post_data['gender']);
            $submission->setGender($selected_gender);

            // recruitment status
            $selected_recruitment_status = $recruitment_status_repository->find($post_data['recruitment-status']);
            $submission->setRecruitmentStatus($selected_recruitment_status);

            // removing all team to readd
            foreach($submission->getCountry() as $country) {
                $submission->removeCountry($country);
                $em->remove($country);
                $em->flush();
            }

            if(isset($post_data['country'])) {
                foreach($post_data['country'] as $key => $country) {

                    $country_obj = $country_repository->find($country['country_id']);

                    // check if exists
                    $submission_country = $submission_country_repository->findOneBy(array(
                        'submission' => $submission, 
                        'country' => $country_obj,
                    ));

                    // var_dump($post_data);die;

                    // if not exists, create the new submission_country
                    if(!$submission_country) {
                        $submission_country = new SubmissionCountry();
                        $submission_country->setSubmission($submission);
                        $submission_country->setCountry($country_obj);
                        $submission_country->setParticipants($country['participants']);

                    } else {
                        $submission_country->setParticipants($country['participants']);
                    }

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($submission_country);
                    $em->flush();

                    // add in submission
                    $submission->addCountry($submission_country);
                }
            }

            $submission->setInterventions($post_data['interventions']);
            
            $submission->setPrimaryOutcome($post_data['primary-outcome']);
            $submission->setSecondaryOutcome($post_data['secondary-outcome']);

            $submission->setGeneralProcedures($post_data['general-procedures']);
            $submission->setAnalysisPlan($post_data['analysis-plan']);
            $submission->setEthicalConsiderations($post_data['ethical-considerations']);

            $em = $this->getDoctrine()->getManager();
            $em->persist($submission);
            $em->flush();

            $session->getFlashBag()->add('success', $translator->trans("Third step saved with sucess."));
            return $this->redirectToRoute('submission_new_fourth_step', array('submission_id' => $submission->getId()), 301);
        }

        return $output;
    }

    /**
     * @Route("/submission/new/{submission_id}/fourth", name="submission_new_fourth_step")
     * @Template()
     */
    public function FourthStepAction($submission_id)
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();

        $submission_repository = $em->getRepository('Proethos2ModelBundle:Submission');
        $submission_cost_repository = $em->getRepository('Proethos2ModelBundle:SubmissionCost');
        $submission_task_repository = $em->getRepository('Proethos2ModelBundle:SubmissionTask');
        $user_repository = $em->getRepository('Proethos2ModelBundle:User');
        $submission_clinical_trial_repository = $em->getRepository('Proethos2ModelBundle:SubmissionClinicalTrial');
        $clinical_trial_name_repository = $em->getRepository('Proethos2ModelBundle:ClinicalTrialName');

        // getting the current submission
        $submission = $submission_repository->find($submission_id);
        $output['submission'] = $submission;

        $clinical_trial_names = $clinical_trial_name_repository->findByStatus(true);
        $output['clinical_trial_names'] = $clinical_trial_names;

        if (!$submission or $submission->getIsSended()) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }

        $allow_to_edit_submission = true;
        $user = $this->get('security.token_storage')->getToken()->getUser();
        // if current user is not owner, check the team
        if ($user != $submission->getOwner()) {
            $allow_to_edit_submission = false;
            foreach($submission->getTeam() as $team_member) {
                // if current user = some team member, than it allows to edit  
                if ($user == $team_member) {
                    $allow_to_edit_submission = true;
                }
            }
        }
        if (!$allow_to_edit_submission) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }
        
        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {

            // getting post data
            $post_data = $request->request->all();

            // checking required files
            $required_fields = array('funding-source', 'primary-sponsor');
            foreach($required_fields as $field) {
                if(!isset($post_data[$field]) or empty($post_data[$field])) {
                    $session->getFlashBag()->add('error', $translator->trans("Field '$field' is required."));
                    return $output;
                }
            }

            // removing all clinical_trial to rewrite
            foreach($submission->getClinicalTrial() as $trial) {
                $submission->removeClinicalTrial($trial);
                $em->remove($trial);
                $em->flush();
            }


            if(isset($post_data['clinical-trial'])) {

                $submission->setClinicalTrialSecondary($post_data['clinical-trial-second']);

                foreach($post_data['clinical-trial'] as $key => $trial_data) {

                    $trial_name = $clinical_trial_name_repository->find($trial_data['name-id']);
                    $date = NULL;
                    if (!empty($trial_data['date'])) {
                        $date = new \DateTime($trial_data['date']);
                    }
                    
                    // check if exists
                    $trial = $submission_clinical_trial_repository->findOneBy(array(
                        'submission' => $submission, 
                        'name' => $trial_name,
                        'date' => $date,
                        'number' => $trial_data['number'],
                    ));

                    if(!$trial) {
                        $trial = new SubmissionClinicalTrial();
                        $trial->setSubmission($submission);
                        $trial->setName($trial_name);
                        $trial->setNumber($trial_data['number']);
                        $trial->setDate($date);
                    }
                    
                    $em->persist($trial);
                    $em->flush();

                    // add in submission
                    $submission->addClinicalTrial($trial);
                }
            }

            // removing all team to readd
            foreach($submission->getBudget() as $budget) {
                $submission->removeBudget($budget);
                $em->remove($budget);
                $em->flush();
            }

            if(isset($post_data['budget'])) {
                foreach($post_data['budget'] as $key => $cost) {

                    // check if exists
                    $submission_cost = $submission_cost_repository->findOneBy(array(
                        'submission' => $submission, 
                        'description' => $cost['description'],
                        'quantity' => $cost['quantity'],
                        'unit_cost' => $cost['unit_cost'],
                    ));

                    // if not exists, create the new submission_cost
                    if(!$submission_cost) {
                        $submission_cost = new SubmissionCost();
                        $submission_cost->setSubmission($submission);
                        $submission_cost->setDescription($cost['description']);
                        $submission_cost->setQuantity($cost['quantity']);
                        $submission_cost->setUnitCost($cost['unit_cost']);
                    }

                    $em->persist($submission_cost);
                    $em->flush();

                    // add in submission
                    $submission->addBudget($submission_cost);
                }
            }

            $submission->setFundingSource($post_data['funding-source']);
            $submission->setPrimarySponsor($post_data['primary-sponsor']);
            $submission->setSecondarySponsor($post_data['secondary-sponsor']);

            // removing all schedule to readd
            foreach($submission->getSchedule() as $schedule) {
                $submission->removeSchedule($schedule);
                $em->remove($schedule);
                $em->flush();
            }

            if(isset($post_data['schedule'])) {
                foreach($post_data['schedule'] as $key => $task) {

                    // check if exists
                    $submission_task = $submission_task_repository->findOneBy(array(
                        'submission' => $submission, 
                        'description' => $task['description'],
                        'init' => new \DateTime($task['init']),
                        'end' => new \DateTime($task['end']),
                    ));

                    // if not exists, create the new submission_task
                    if(!$submission_task) {
                        $submission_task = new SubmissionTask();
                        $submission_task->setSubmission($submission);
                        $submission_task->setDescription($task['description']);
                        $submission_task->setInit(new \DateTime($task['init']));
                        $submission_task->setEnd(new \DateTime($task['end']));
                    }

                    $em->persist($submission_task);
                    $em->flush();

                    // add in submission
                    $submission->addSchedule($submission_task);
                }
            }

            $em->persist($submission);
            $em->flush();
            
            $session->getFlashBag()->add('success', $translator->trans("Fourth step saved with sucess."));
            return $this->redirectToRoute('submission_new_fifth_step', array('submission_id' => $submission->getId()), 301);
        }

        return $output;
    }

    /**
     * @Route("/submission/new/{submission_id}/fifth", name="submission_new_fifth_step")
     * @Template()
     */
    public function FifthStepAction($submission_id)
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();

        $submission_repository = $em->getRepository('Proethos2ModelBundle:Submission');
        $user_repository = $em->getRepository('Proethos2ModelBundle:User');
        $submission_country_repository = $em->getRepository('Proethos2ModelBundle:SubmissionCountry');

        // getting the current submission
        $submission = $submission_repository->find($submission_id);
        $output['submission'] = $submission;

        if (!$submission or $submission->getIsSended()) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }

        $allow_to_edit_submission = true;
        $user = $this->get('security.token_storage')->getToken()->getUser();
        // if current user is not owner, check the team
        if ($user != $submission->getOwner()) {
            $allow_to_edit_submission = false;
            foreach($submission->getTeam() as $team_member) {
                // if current user = some team member, than it allows to edit  
                if ($user == $team_member) {
                    $allow_to_edit_submission = true;
                }
            }
        }
        if (!$allow_to_edit_submission) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }
        
        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {

            // getting post data
            $post_data = $request->request->all();

            // print '<pre>';
            // var_dump($post_data);die;
            
            // checking required files
            $required_fields = array('bibliography', 'scientific-contact', 'prior-ethical-approval');
            foreach($required_fields as $field) {
                if(!isset($post_data[$field]) or empty($post_data[$field])) {
                    $session->getFlashBag()->add('error', $translator->trans("Field '$field' is required."));
                    return $output;
                }
            }
    
            $submission->setBibliography($post_data['bibliography']);        
            $em = $this->getDoctrine()->getManager();
            $em->persist($submission);
            $em->flush();

            $submission->setScientificContact($post_data['scientific-contact']);                   
            $submission->setPriorEthicalApproval(($post_data['prior-ethical-approval'] == 'Y') ? true : false);                   

            $em = $this->getDoctrine()->getManager();
            $em->persist($submission);
            $em->flush();

            $session->getFlashBag()->add('success', $translator->trans("Fifth step saved with sucess."));
            return $this->redirectToRoute('submission_new_sixth_step', array('submission_id' => $submission->getId()), 301);
        }

        return $output;
    }

    /**
     * @Route("/submission/new/{submission_id}/sixth", name="submission_new_sixth_step")
     * @Template()
     */
    public function SixtyStepAction($submission_id)
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $submission_repository = $em->getRepository('Proethos2ModelBundle:Submission');
        $upload_type_repository = $em->getRepository('Proethos2ModelBundle:UploadType');
        $submission_upload_repository = $em->getRepository('Proethos2ModelBundle:SubmissionUpload');
        
        // getting the current submission
        $submission = $submission_repository->find($submission_id);
        $output['submission'] = $submission;

        $upload_types = $upload_type_repository->findByStatus(true);
        $output['upload_types'] = $upload_types;

        if (!$submission or $submission->getIsSended()) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }

        $allow_to_edit_submission = true;
        $user = $this->get('security.token_storage')->getToken()->getUser();
        // if current user is not owner, check the team
        if ($user != $submission->getOwner()) {
            $allow_to_edit_submission = false;
            foreach($submission->getTeam() as $team_member) {
                // if current user = some team member, than it allows to edit  
                if ($user == $team_member) {
                    $allow_to_edit_submission = true;
                }
            }
        }
        if (!$allow_to_edit_submission) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }
        
        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {

            // getting post data
            $post_data = $request->request->all();

            $file = $request->files->get('new-atachment-file');
            if(!empty($file)) {
                
                if(!isset($post_data['new-atachment-type']) or empty($post_data['new-atachment-type'])) {
                    $session->getFlashBag()->add('error', $translator->trans("Field 'new-atachment-type' is required."));
                    return $output;                
                }
                
                $upload_type = $upload_type_repository->find($post_data['new-atachment-type']);
                if (!$upload_type) {
                    throw $this->createNotFoundException($translator->trans('No upload type found'));
                    return $output;                
                }

                $submission_upload = new SubmissionUpload();
                $submission_upload->setSubmission($submission);
                $submission_upload->setUploadType($upload_type);
                $submission_upload->setUser($user);
                $submission_upload->setFile($file);
                $submission_upload->setSubmissionNumber($submission->getNumber());

                $em = $this->getDoctrine()->getManager();
                $em->persist($submission_upload);
                $em->flush();

                $submission->addAttachment($submission_upload);
                $em = $this->getDoctrine()->getManager();
                $em->persist($submission);
                $em->flush();
                
                $session->getFlashBag()->add('success', $translator->trans("File uploaded with sucess."));
                return $this->redirectToRoute('submission_new_sixth_step', array('submission_id' => $submission->getId()), 301);
                
            }

            if(isset($post_data['delete-attachment-id']) and !empty($post_data['delete-attachment-id'])) {

                $submission_upload = $submission_upload_repository->find($post_data['delete-attachment-id']);
                if($submission_upload) {
                    
                    $em->remove($submission_upload);
                    $em->flush();
                    $session->getFlashBag()->add('success', $translator->trans("File removed with sucess."));
                    return $this->redirectToRoute('submission_new_sixth_step', array('submission_id' => $submission->getId()), 301);
                }
            }
 
            return $this->redirectToRoute('submission_new_seventh_step', array('submission_id' => $submission->getId()), 301);
        }

        return $output;
    }

    /**
     * @Route("/submission/new/{submission_id}/seventh", name="submission_new_seventh_step")
     * @Template()
     */
    public function SeventhStepAction($submission_id)
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $submission_repository = $em->getRepository('Proethos2ModelBundle:Submission');
        
        // getting the current submission
        $submission = $submission_repository->find($submission_id);
        $output['submission'] = $submission;

        if (!$submission or $submission->getIsSended()) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }

        $allow_to_edit_submission = true;
        $user = $this->get('security.token_storage')->getToken()->getUser();
        // if current user is not owner, check the team
        if ($user != $submission->getOwner()) {
            $allow_to_edit_submission = false;
            foreach($submission->getTeam() as $team_member) {
                // if current user = some team member, than it allows to edit  
                if ($user == $team_member) {
                    $allow_to_edit_submission = true;
                }
            }
        }
        if (!$allow_to_edit_submission) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }
        
        // Revisions
        $revisions = array(); 
        $final_status = true;

        $text = $translator->trans('Team') . " (" . count($submission->getTeam())+1 . " " . $translator->trans('member(s)') . ")";
        $item = array('text' => $text, 'status' => true);
        $revisions[] = $item;

        // $text = $translator->trans('Files Submited') . " (" . count($submission->getAttachments()) . " " . $translator->trans('files(s)') . ")";
        // $item = array('text' => $text, 'status' => true);
        // if(count($submission->getAttachments()) == 0) {
        //     $item = array('text' => $text, 'status' => false);
        //     $final_status = false;
        // }
        // $revisions[] = $item;
        
        $text = $translator->trans('Abstract');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getAbstract())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;
        
        $text = $translator->trans('Keywords');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getKeywords())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;
        
        $text = $translator->trans('Introduction');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getIntroduction())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;
        
        $text = $translator->trans('Justification');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getJustification())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;
        
        $text = $translator->trans('Goals');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getGoals())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;
        
        $text = $translator->trans('Study Design');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getStudyDesign())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;
        
        $text = $translator->trans('Gender');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getGender())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;

        $text = $translator->trans('Minimum Age');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getMinimumAge())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;

        $text = $translator->trans('MAximum Age');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getMaximumAge())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;

        $text = $translator->trans('Inclusion Criteria');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getInclusionCriteria())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;

        $text = $translator->trans('Exclusion Criteria');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getExclusionCriteria())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;

        $text = $translator->trans('Inicial recruitment estimation date');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getRecruitmentInitDate())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;

        $text = $translator->trans('Interventions');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getInterventions())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;

        $text = $translator->trans('Primary Outcome');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getPrimaryOutcome())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;

        $text = $translator->trans('Funding Source');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getFundingSource())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;

        $text = $translator->trans('Primary Sponsor');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getPrimarySponsor())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;

        $text = $translator->trans('Bibliography');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getBibliography())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;
        
        $text = $translator->trans('Scientific Contact');
        $item = array('text' => $text, 'status' => true);
        if(empty($submission->getScientificContact())) {
            $item = array('text' => $text, 'status' => false);
            $final_status = false;
        }
        $revisions[] = $item;

        $output['revisions'] = $revisions;
        $output['final_status'] = $final_status;
        
        // checking if was a post request
        if($this->getRequest()->isMethod('POST')) {

            // getting post data
            $post_data = $request->request->all();

            if($final_status) {

                if($post_data['accept-terms'] == 'on') {

                    // gerando um novo pdf
                    try {
                        $html = $this->renderView(
                            'Proethos2CoreBundle:NewSubmission:showPdf.html.twig',
                            $output
                        );

                        $pdf = $this->get('knp_snappy.pdf');

                        // setting margins
                        $pdf->getInternalGenerator()->setOption('margin-top', '50px');
                        $pdf->getInternalGenerator()->setOption('margin-bottom', '50px');
                        $pdf->getInternalGenerator()->setOption('margin-left', '20px');
                        $pdf->getInternalGenerator()->setOption('margin-right', '20px');
                        
                        // adding pdf to tmp file
                        $filepath = "/tmp/" . date("Y-m-d") . "-submission.pdf";
                        file_put_contents($filepath, $pdf->getOutputFromHtml($html));

                        $submission_number = count($submission->getProtocol()->getSubmission());                        

                        // send tmp file to upload class and save
                        $pdfFile = new SubmissionUpload();
                        $pdfFile->setSubmission($submission);
                        $pdfFile->setSimpleFile($filepath);
                        $pdfFile->setUser($user);
                        $pdfFile->setSubmissionNumber($submission->getNumber());
                        $em->persist($pdfFile);
                        $em->flush();

                    } catch(\RuntimeException $e) {
                        
                        $session->getFlashBag()->add('error', $translator->trans('Problems to generate PDF. Please contact the administrator.'));
                        return $output;
                    }

                    // updating protocol and setting status
                    $protocol = $submission->getProtocol();
                    $protocol->setStatus("S");
                    $protocol->setDateInformed(new \DateTime());
                    $protocol->setUpdatedIn(new \DateTime());
                    $em->persist($protocol);
                    $em->flush();

                    $submission->setIsSended(true);
                    $em->persist($submission);
                    $em->flush();

                    $protocol_history = new ProtocolHistory();
                    $protocol_history->setProtocol($protocol);

                    $protocol_history->setMessage($translator->trans("Submission of protocol."));
                    $em->persist($protocol_history);
                    $em->flush();


                    $session->getFlashBag()->add('success', $translator->trans("Protocol submitted with sucess!"));
                    return $this->redirectToRoute('protocol_show_protocol', array('protocol_id' => $protocol->getId()), 301);

                } else {
                    $session->getFlashBag()->add('error', $translator->trans("You must to accept the terms and conditions."));
                }
            } else {
                $session->getFlashBag()->add('error', $translator->trans('You have revision pendencies.'));
            }
        }

        return $output;
    }

    /**
     * @Route("/submission/new/{submission_id}/pdf", name="submission_generate_pdf")
     * @Template()
     */
    public function showPdfAction($submission_id)
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();

        $submission_repository = $em->getRepository('Proethos2ModelBundle:Submission');
        
        // getting the current submission
        $submission = $submission_repository->find($submission_id);
        $output['submission'] = $submission;

        if (!$submission or $submission->getIsSended()) {
            throw $this->createNotFoundException($translator->trans('No submission found'));
        }

        $html = $this->renderView(
            'Proethos2CoreBundle:NewSubmission:showPdf.html.twig',
            $output
        );

        $pdf = $this->get('knp_snappy.pdf');

        // setting margins
        $pdf->getInternalGenerator()->setOption('margin-top', '50px');
        $pdf->getInternalGenerator()->setOption('margin-bottom', '50px');
        $pdf->getInternalGenerator()->setOption('margin-left', '20px');
        $pdf->getInternalGenerator()->setOption('margin-right', '20px');

        return new Response(
            $pdf->getOutputFromHtml($html),
            200,
            array(
                'Content-Type'          => 'application/pdf'
            )
        );
    }
}

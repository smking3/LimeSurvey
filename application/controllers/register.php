<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * LimeSurvey
 * Copyright (C) 2007-2011 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 *	$Id: Admin_Controller.php 11256 2011-10-25 13:52:18Z c_schmitz $
 */

 /**
  * register
  *
  * @package LimeSurvey
  * @copyright 2011
  * @version $Id$
  * @access public
  */
 class register extends LSCI_Controller {

	/**
	 * register::__construct()
	 * Constructor
	 * @return
	 */
	function __construct()
	{
		parent::__construct();
	}
    
    function ajaxregisterform($surveyid)
    {
        $this->load->helper('database');
        $this->load->helper('replacements');
        $redata = compact(array_keys(get_defined_vars()));
        $thistpl = $this->config->item("standardtemplaterootdir").'/default';
        $surveyid = sanitize_int($surveyid);
        $squery = "SELECT a.expires, a.startdate
                      FROM ".$this->db->dbprefix('surveys')." AS a
                      WHERE a.sid = $surveyid "; 
                     
                                            
        $sresult = db_execute_assoc($squery) or show_error("Couldn't execute $squery");
        
            
        $row = $sresult->row_array();
        
        $data['sid'] = $surveyid;
        $data['startdate'] = $row['startdate'];
        $data['enddate'] = $row['expires'];
        
        $baselang = GetBaseLanguageFromSurveyID($surveyid);
        $this->load->library('Limesurvey_lang',array($baselang));
        $data['clang'] = $this->limesurvey_lang;
        echo templatereplace(file_get_contents("$thistpl/register.pstpl"),array(),$redata,'register.php',false,NULL,$data);
        
    }

    /**
     * register::index()
     * Process register form data and take appropriate action
     * @return
     */
    function index()
    {

        $surveyid=$this->input->post('sid');
        $postlang=$this->input->post('lang');
        
        if (!$surveyid)
        {
            redirect();
        }
        $this->load->helper('database');

        $usquery = "SELECT stg_value FROM ".$this->db->dbprefix."settings_global where stg_name='SessionName'";
        $usresult = db_execute_assoc($usquery,'',true);          //Checked
        if ($usresult->num_rows() > 0)
        {
            $usrow = $usresult->row_array();
            $stg_SessionName=$usrow['stg_value'];
            @session_name($stg_SessionName.'-runtime-'.$surveyid);
        }
        else
        {
            session_name("LimeSurveyRuntime-$surveyid");
        }

        session_set_cookie_params(0,$this->config->item('relativeurl').'/');
        session_start();

        // Get passed language from form, so that we dont loose this!
        if (!isset($postlang) || $postlang == "" || !$postlang )
        {
            $baselang = GetBaseLanguageFromSurveyID($surveyid);
            $this->load->library('Limesurvey_lang',array($baselang));
            $clang = $this->limesurvey_lang;
        } else {
            $this->load->library('Limesurvey_lang',array($postlang));
            $clang = $this->limesurvey_lang;
            $baselang = $postlang;
        }

        $thissurvey=getSurveyInfo($surveyid,$baselang);

        $register_errormsg = "";
        $_POST = $this->input->post();
        // Check the security question's answer
        if (function_exists("ImageCreate") && captcha_enabled('registrationscreen',$thissurvey['usecaptcha']) )
        {
            if (!isset($_POST['loadsecurity']) ||
            !isset($_SESSION['secanswer']) ||
            $_POST['loadsecurity'] != $_SESSION['secanswer'])
            {
                $register_errormsg .= $clang->gT("The answer to the security question is incorrect.")."<br />\n";
            }
        }

        //Check that the email is a valid style address
        if (!validate_email($_POST['register_email']))
        {
            $register_errormsg .= $clang->gT("The email you used is not valid. Please try again.");
        }

        if ($register_errormsg != "")
        {
            redirect($surveyid);
        }
        
        $dbprefix = $this->db->dbprefix;
        //Check if this email already exists in token database
        $query = "SELECT email FROM {$dbprefix}tokens_$surveyid\n"
        . "WHERE email = '".sanitize_email($_POST['register_email'])."'";
        $result = db_execute_assoc($query) or show_error("Unable to execute this query : \n <br/>".$query."<br />");   //Checked)
        if (($result->num_rows()) > 0)
        {
            $register_errormsg=$clang->gT("The email you used has already been registered.");
            redirect($surveyid);
            //include "index.php";
            //exit;
        }

        $mayinsert = false;

    	// Get the survey settings for token length
    	$this->load->model("surveys_model");
    	$tlresult = $this->surveys_model->getSomeRecords(array("tokenlength"),array("sid"=>$surveyid));
    	$tlrow = $tlresult->row_array();
    	$tokenlength = $tlrow['tokenlength'];
    	//if tokenlength is not set or there are other problems use the default value (15)
    	if(!isset($tokenlength) || $tokenlength == '')
    	{
    		$tokenlength = 15;
    	}

        while ($mayinsert != true)
        {
            $newtoken = sRandomChars($tokenlength);
            $ntquery = "SELECT * FROM {$dbprefix}tokens_$surveyid WHERE token='$newtoken'";
            $ntresult = db_execute_assoc($ntquery); //Checked
            if (!$ntresult->num_rows()) {$mayinsert = true;}
        }

        $postfirstname=sanitize_xss_string(strip_tags($_POST['register_firstname']));
        $postlastname=sanitize_xss_string(strip_tags($_POST['register_lastname']));
        $starttime = sanitize_xss_string($this->input->post('startdate'));
        $endtime = sanitize_xss_string($this->input->post('enddate'));        
        /*$postattribute1=sanitize_xss_string(strip_tags(returnglobal('register_attribute1')));
         $postattribute2=sanitize_xss_string(strip_tags(returnglobal('register_attribute2')));   */

        //Insert new entry into tokens db
        $query = "INSERT INTO {$dbprefix}tokens_$surveyid\n"
        . "(firstname, lastname, email, emailstatus, token"; 
        
        if ($starttime && $endtime)
        $query .= ", validfrom, validuntil"; 
        
        $query .=")\n"      
        . "VALUES ('$postfirstname', '$postlastname', '".$_POST['register_email']."', 'OK', '$newtoken'";
        
        if ($starttime && $endtime)
        $query .= ",$starttime,$endtime";
        
        $query .=")";
        $result = db_execute_assoc($query);
        /**
        $result = $connect->Execute($query, array($postfirstname,
        $postlastname,
        returnglobal('register_email'),
                                                  'OK',
        $newtoken)

        //                             $postattribute1,   $postattribute2)
        ) or safe_die ($query."<br />".$connect->ErrorMsg());  //Checked - According to adodb docs the bound variables are quoted automatically
        */
        $tid=$this->db->insert_id(); //$connect->Insert_ID("{$dbprefix}tokens_$surveyid","tid");


        $fieldsarray["{ADMINNAME}"]=$thissurvey['adminname'];
        $fieldsarray["{ADMINEMAIL}"]=$thissurvey['adminemail'];
        $fieldsarray["{SURVEYNAME}"]=$thissurvey['name'];
        $fieldsarray["{SURVEYDESCRIPTION}"]=$thissurvey['description'];
        $fieldsarray["{FIRSTNAME}"]=$postfirstname;
        $fieldsarray["{LASTNAME}"]=$postlastname;
        $fieldsarray["{EXPIRY}"]=$thissurvey["expiry"];

        $message=$thissurvey['email_register'];
        $subject=$thissurvey['email_register_subj'];


        $from = "{$thissurvey['adminname']} <{$thissurvey['adminemail']}>";

        if (getEmailFormat($surveyid) == 'html')
        {
            $useHtmlEmail = true;
            $surveylink = site_url(''.$surveyid.'/lang-'.$baselang.'/tk-'.$newtoken);
            $optoutlink = site_url('optout/local/'.$surveyid.'/'.$baselang.'/'.$newtoken);
            $optinlink = site_url('optin/local/'.$surveyid.'/'.$baselang.'/'.$newtoken);
            $fieldsarray["{SURVEYURL}"]="<a href='$surveylink'>".$surveylink."</a>";
            $fieldsarray["{OPTOUTURL}"]="<a href='$optoutlink'>".$optoutlink."</a>";
            $fieldsarray["{OPTINURL}"]="<a href='$optinlink'>".$optinlink."</a>";
        }
        else
        {
            $useHtmlEmail = false;
            $fieldsarray["{SURVEYURL}"]=site_url(''.$surveyid.'/lang-'.$baselang.'/tk-'.$newtoken);
            $fieldsarray["{OPTOUTURL}"]= site_url('optout/local/'.$surveyid.'/'.$baselang.'/'.$newtoken);
            $fieldsarray["{OPTINURL}"]= site_url('optin/local/'.$surveyid.'/'.$baselang.'/'.$newtoken);
        }

        $message=ReplaceFields($message, $fieldsarray);
        $subject=ReplaceFields($subject, $fieldsarray);

        $html=""; //Set variable

        if (SendEmailMessage($message, $subject, $_POST['register_email'], $from, $sitename,$useHtmlEmail,getBounceEmail($surveyid)))
        {
            // TLR change to put date into sent
            //	$query = "UPDATE {$dbprefix}tokens_$surveyid\n"
            //			."SET sent='Y' WHERE tid=$tid";
            $today = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i", $timeadjust);
            $query = "UPDATE {$dbprefix}tokens_$surveyid\n"
            ."SET sent='$today' WHERE tid=$tid";
            $result=db_execute_assoc($query) or show_error("Unable to execute this query : $query<br />");     //Checked
            $html="<center>".$clang->gT("Thank you for registering to participate in this survey.")."<br /><br />\n".$clang->gT("An email has been sent to the address you provided with access details for this survey. Please follow the link in that email to proceed.")."<br /><br />\n".$clang->gT("Survey administrator")." {ADMINNAME} ({ADMINEMAIL})";
            $html=ReplaceFields($html, $fieldsarray);
            $html .= "<br /><br /></center>\n";
        }
        else
        {
            $html="Email Error";
        }

        //PRINT COMPLETED PAGE
        if (!$thissurvey['template'])
        {
            $thistpl=sGetTemplatePath(validate_templatedir('default'));
        }
        else
        {
            $thistpl=sGetTemplatePath(validate_templatedir($thissurvey['template']));
        }

        sendcacheheaders();
        doHeader();

        foreach(file("$thistpl/startpage.pstpl") as $op)
        {
            echo templatereplace($op);
        }
        foreach(file("$thistpl/survey.pstpl") as $op)
        {
            echo "\t".templatereplace($op);
        }
        echo $html;
        foreach(file("$thistpl/endpage.pstpl") as $op)
        {
            echo templatereplace($op);
        }
        doFooter();
    }

 }
<?php
class Pr extends CI_Controller {
	var $data = array();
	var $data_email = array();

	/* Defualt method*/
	function __construct() {
		parent::__construct();
		$this->load->library('form_validation');
		$this->load->library('session');
		$this->load->library('email');
		$this->load->helper(array('form', 'url'));
		$this->load->model('commonmodel');
		$this->load->model('front_model');

		/* initialize Paypal recurring library*/
		$param = array('biz_api.gmail.com','13********','AFcWxV21C7fd0***********************8');
		$this->load->library('Paypal_Recurring',$param,'pr');
	}

	/* Default Method*/
	function index(){
		redirect(base_url().'dopayment/');
	}

	/* Method for Start Recurring Payment*/
	function dopayment(){
		$plan_info 	  	 = $this->session->userdata('upgrade_plan_details');
		$userInfo 	 	   	 = $this->commonmodel->get_item_by_iUserId('user',$this->session->userdata('Id'));
		$plan_payment_info  	= $this->commonmodel->get_item_by_iUserId('plan_payment',$this->session->userdata('Id'));
		$profile_start_date 		= date("Y-m-d H:i:s");
		$billing_period 		= 'Day';
		$billing_frequency 		= '1';
		$totalbillingcycles 		= 0;
		$desc 			= $plan_info['desc'];
		$amount			= $plan_info['usprice'];
		$currency_code		= 'USD';
		$card_type 		= $this->input->post('CCtype');
		$credit_card_number 	= $this->input->post('CCNo');
		$exp_date		= $this->input->post('CCExpiresMonth').$this->input->post('CCExpiresYear');
		$cvv2 			= $this->input->post('cvv2');
		$billing_email		= $this->session->userdata('business_email');
		$last_name 		= $userInfo->vLastName;
		$first_name 		= $userInfo->vFirstName;
		$userEmail 		= $userInfo->vEmail;

		// Make a Data array to from post and set in variables
		$data 						= array();
		$data['PROFILESTARTDATE']  	= $profile_start_date; // Profile start date
		$data['DESC'] 		= $desc;				//Description

		//Billing Period Details Fields
		$data['BILLINGPERIOD'] 	= $billing_period;		//Billing period
		$data['BILLINGFREQUENCY'] 	= $billing_frequency;
		$data['TOTALBILLINGCYCLES'] = $totalbillingcycles;  //$plan_duration;
		$data['AMT'] 		= $amount;  			//amount
		$data['INITAMT'] 		= $amount;  			//amount
		$data['CURRENCYCODE'] 	= $currency_code; 		//currency code

		//Credit Card Details Fields
		$data['CREDITCARDTYPE'] 	= $card_type; 			// card type
		$data['ACCT'] 		= $credit_card_number; 	//credit card number
		$data['EXPDATE'] 		= $exp_date;			//exp date
		$data['CVV2'] 		= $cvv2;				//cvv2

		//Payer Information Fields
		$data['EMAIL'] 		= $billing_email;		//Email id of user
		$data['FIRSTNAME'] 	= $first_name;			//firstName of user
		$data['LASTNAME'] 		= $last_name;			//lastName of user
		$data['BUSINESS'] 		= 'Yolo';

		//call method for Create Profile
		$paypal_recurring_profileId 	= '';
		$paypal_recurring_transactionid = '';
		$paypal_recurring_ack_status 	= '';

		if($userInfo->recurring_profile_id != '' && !(empty($userInfo->recurring_profile_id))){
			//Before create a new profile need to close old once
			$createProfile= $this->pr->paypal_recurring_manage_profile_status($userInfo->recurring_profile_id,'Suspend','Suspend');
			$createProfile= $this->pr->paypal_recurring_create_profile($data);
		} else {
			//Create a new profile
			$createProfile= $this->pr->paypal_recurring_create_profile($data);
		}


		/* set data in varible */
		$paypal_recurring_profileId  		= @$createProfile['PROFILEID'];
		if(isset($createProfile['TRANSACTIONID'])){
			$paypal_recurring_transactionid = @$createProfile['TRANSACTIONID'];
		}else{
			$paypal_recurring_transactionid = @$createProfile['PROFILEID'];
		}
		$paypal_recurring_ack_status 		= @$createProfile['ACK'];

		@$iPlanPeriod       		= '28';
		@$dPlanStartDate    	= date('Y-m-d H:i:s');
		@$dPlanExpiryDate   	= date("Y-m-d H:i:s",strtotime("+28 day", strtotime(@$dPlanStartDate)));
		@$eSubscription_mode    	= 'Inactive';

		if($paypal_recurring_ack_status == 'Success'){
			@$eSubscription_mode    = 'Active';
			$BuisnessData = array(
				'iPlanId'            => $plan_info['id'],
				'iPlanPeriod'        => $iPlanPeriod,
				'dPlanStartDate'     => $dPlanStartDate,
				'dPlanExpiryDate'    => $dPlanExpiryDate,
				'eSubscription_mode' => $eSubscription_mode,
				);
			$this->db->set($BuisnessData);
			$this->db->where('iUserId',$this->session->userdata('Id'));
			$this->db->update('business');

        //Load bayur data using profile Id
			$getBuyerDetails = $this->pr->paypal_recurring_get_profile_details($paypal_recurring_profileId);

			/*Update  plan_payment table*/
			$planTabledata = array(
				'iUserId'     => $this->session->userdata('Id'),
				'iPlanId'     => $plan_info['id'],
				'iTrans_id'   => @$paypal_recurring_transactionid,
				'fAmount'     => $getBuyerDetails['AMT'],
				'eCurrency'   => $getBuyerDetails['CURRENCYCODE'],
				'dCreated'    => date('Y-m-d'),
				'eStatus'     => 'Active'
				);
			$this->commonmodel->insert('plan_payment',$planTabledata);

			/* update user table */
			$userTabledata = array('recurring_profile_id'=> $getBuyerDetails['PROFILEID']);
			$this->commonmodel->update_by_iUserId('user',$this->session->userdata('Id'),$userTabledata);

			/*SEND E-MAIL*/

			//English mail content 
                        $this->data['paypal_recurring_transactionid'] = @$paypal_recurring_transactionid;
			
                        //Language wise conteent Data
                        
                        if($this->session->userdata('site_lang') != '' && $this->session->userdata('site_lang') != 'portuguese' )
                        {
                            $this->data['content_message'] = 'Este email tem o unico intuito de notificá-lo de que seu plano foi atualizado com sucesso.Veja abaixo a sua Identificação de Pagamento';
                            $this->data['text_hi'] = 'Ola,';
                            $this->data['text_welcome'] = 'Bem-vindo ao  App - Serviço de Notificação de pagamento.';
                            $this->data['text_TransactionID'] = 'Identificação de Pagamento:';
                            $this->data['text_refer'] = 'Por favor, consulte o FAQ Termos e privacidade para obter mais informações sobre este serviço ';
                            $this->data['text_thanks'] = 'Obrigado';
                        } 
                        else if($this->session->userdata('site_lang') != '' && $this->session->userdata('site_lang') != 'spanish' )
                        {
                            $this->data['content_message'] = 'Esta es una notificación que el upgrade de su plan para los próximos 28 días se concluyó de forma exitosa. Encuentre a continuación el comprobante de su transacción.';
                            $this->data['text_hi'] = 'Hola,';
                            $this->data['text_welcome'] = 'Bienvenido a  App – servicio de Notificación de Pago.';
                            $this->data['text_TransactionID'] = 'Transacción:';
                            $this->data['text_refer'] = 'Para obtener informaciones adicionales, vea nuestra área de preguntas frecuentes (FAQ\'s), Términos o Privacidad.';
                            $this->data['text_thanks'] = 'Gracias';
                        }
                        else
                        {
                            $this->data['content_message'] = 'This is to notify you that your plan has been successfully changed. Please find below your payment id';
                            $this->data['text_hi'] = 'Hi,';
                            $this->data['text_welcome'] = 'Welcome to the  App - Payment Notification service.';
                            $this->data['text_TransactionID'] = 'Payment ID:';
                            $this->data['text_refer'] = 'Please refer to FAQ\'s, Terms and Privacy for additional information regarding this service';
                            $this->data['text_thanks'] = 'Thank you';
                        }
                            
                        $message = $this->load->view('mail/subscription',$this->data,TRUE);

			$this->email->initialize(array('mailtype' => 'html'));
			$uid = $this->session->userdata('Id');
			$this->email->from($this->config->item('mail_from'), ' App Payment Notification');
			$this->email->to(trim($userEmail));
			$this->email->cc('bhavesh561988@gmail.com');
			$this->email->subject("$uid - PAYMENT SUCESS");
			$this->email->message($message);
			$this->email->send();
			/* END SEND MAIL */

       			 //Set Message and redirect
			$this->session->set_flashdata('paypal_success_message', "Business plan upgrade process completed successfuly !");
			redirect('manageProfile');
		} else {

        	//Set Error and redirect
			$this->session->set_flashdata('paypal_success_message', "Please Enter Proper information..Try again later");
			redirect('manageProfile');
		}
	}
}
?>

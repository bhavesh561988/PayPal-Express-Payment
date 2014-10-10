<?php
/*Controller for Manage Payment for  App Yolo Business Users
*/
class Ipn extends CI_Controller {

var $data = array();

function __construct() {
    parent::__construct();
      $this->load->library('form_validation');
      $this->load->library('session');
      $this->load->library('email');
      $this->load->helper(array('form', 'url'));
      $this->load->model('commonmodel');
      $this->load->model('front_model');
}

function index(){
      /*$userId = 218;
       $businessData = $this->commonmodel->get_planId_by_iUserId('business',$userId);
       echo $businessData;
       exit;*/
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $myPost = array();
    $collumns = array();
    $colvalues = array();
    foreach ($raw_post_array as $keyval){
            $keyval = explode ('=', $keyval);
            if (count($keyval) == 2){
                    $myPost[$keyval[0]] = urldecode($keyval[1]);
                    array_push($collumns, $keyval[0]);
                    array_push($colvalues, "'".mysql_real_escape_string(html_entity_decode(urldecode($keyval[1])))."'");
            }
    }
    // read the post from PayPal system and add 'cmd'
    $req = 'cmd=_notify-validate&';
    $req .= $raw_post_data;
    if(isset($_POST["ipn_track_id"]) && $_POST["ipn_track_id"] != ''){
            $paypalurl = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        // STEP 2: Post IPN data back to paypal to validate
            $ch = curl_init($paypalurl);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
            // In wamp like environments that do not come bundled with root authority certificates,
            // please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path
            // of the certificate as shown below.
            // curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
            if( !($res = curl_exec($ch)) ) {
                    error_log("Got " . curl_error($ch) . " when processing IPN data");
                    curl_close($ch);
                    exit;
            }
            curl_close($ch);


        // STEP 3: Inspect IPN validation result and act accordingly
    	if (strcmp ($res, "VERIFIED") == 0) {
          @$json             = json_decode($_REQUEST['custom']);
          @$planId           = $json->planId;
          @$uid              = $json->userId;
          @$_subscription_id = $_REQUEST['subscr_id'];
          @$_player_email    = $_REQUEST['payer_email'];
          @$customerName     = $_REQUEST['last_name'];
          @$payment_currency = $_REQUEST['mc_currency'];
          @$txn_id           = $_REQUEST['txn_id'];
          @$payment_status   = $_REQUEST['payment_status'];
          @$ePriceType       = $_REQUEST['mc_currency'];
          if(@$uid && $uid != ''){
            @$userDetails = $this->commonmodel->get_item_by_iUserId('user',$uid);
          }
            if($_REQUEST['txn_type'] == 'subscr_signup'){
                /*$payment_amount     = $_REQUEST['amount3'];
                @$iPlanPeriod       = '28';
                @$dPlanStartDate    = date('Y-m-d H:i:s');
                @$dPlanExpiryDate       = date("Y-m-d H:i:s",strtotime("+28 day", strtotime(@$dPlanStartDate)));
                if(@$payment_status=='verified'){
                    @$eSubscription_mode    = 'Active';
                }
                $BuisnessData = array(
                      'iPlanId'           => $planId,
                      'iPlanPeriod'       => $iPlanPeriod,
                      'dPlanStartDate'    => $dPlanStartDate,
                      );
                 if(@$payment_status=='Completed'){
                    $BuisnessData['dPlanExpiryDate'] = $dPlanExpiryDate;
                    $BuisnessData['eSubscription_mode'] = $eSubscription_mode;
                }
                $this->db->set($BuisnessData);
                $this->db->where('iUserId',$uid);
                $this->db->update('business');*/

                /*Update  plan_payment table*/
                /*$planTabledata = array(
                      'iUserId'     => $uid,
                      'iTrans_id'    => $_subscription_id,
                      'fAmount'     => $payment_amount,
                      'eCurrency'   => $ePriceType,
                      'dCreated'    => date('Y-m-d'),
                      'eStatus'     => 'Active'
                      );
                $this->commonmodel->insert('plan_payment',$planTabledata);
                mail('bhavesh.khanpara@indianic.com',"BUSINESS UPGRADE","Business upgraded successfully");*/
            }
            else if($_REQUEST['txn_type'] == 'subscr_payment'){
              if($planId != ''){
                  /*Update Payment Table*/
                  @$payment_amount    = $_POST['payment_gross'];
                  @$iPlanPeriod       = '28';
                  @$dPlanStartDate    = date('Y-m-d H:i:s');
                  @$dPlanExpiryDate   = date("Y-m-d H:i:s",strtotime("+28 day", strtotime(@$dPlanStartDate)));
                  @$eSubscription_mode = 'Active';

                  /*  Update Business details*/
                  $BuisnessData = array(
                        'iPlanId'           =>  $planId,
                        'iPlanPeriod'      => $iPlanPeriod,
                        'dPlanStartDate'    => $dPlanStartDate,
                        );
                  if(@$payment_status=='Completed'){
                      $BuisnessData['dPlanExpiryDate'] = $dPlanExpiryDate;
                      $BuisnessData['eSubscription_mode'] = $eSubscription_mode;
                  }

                  $this->db->set($BuisnessData);
                  $this->db->where('iUserId',$uid);
                  $this->db->update('business');
                  /*  End business details */

                  /*Update  plan_payment table*/
                  $planTabledata = array(
                        'iUserId'     =>  $uid,
                        'iPlanId'     =>  $planId,
                        'iTrans_id'    => $_subscription_id,
                        'fAmount'     => $payment_amount,
                        'eCurrency'   => $ePriceType,
                        'dCreated'    => date('Y-m-d'),
                        'eStatus'     => 'Active'
                        );
                  $this->commonmodel->insert('plan_payment',$planTabledata);

                  /* Send mail */
                  /*$message = 'subscr_payment else if condition';
                  $this->email->from($this->config->item('mail_from'), 'App Auto Upgrade Plan');
                  $this->email->to(trim($userDetails->vEmail));
                  $this->email->subject("User $uid - Your Plan will be auto subscribe for next 28 days");	                            $this->email->message($message);
                  $this->email->send();*/

                  $this->data['paypal_recurring_transactionid'] = @$_subscription_id;
                  $this->data['content_message'] = 'This is to notify that your plan is sucessfully upgrade for next 28 days,please find below your payment subscription id';

                  $message = $this->load->view('mail/subscription',$this->data,TRUE);
                  $this->email->initialize(array('mailtype' => 'html'));
                  $this->email->from($this->config->item('mail_from'), 'App - Payment  Recurring Notification');
                  $this->email->to(trim($userDetails->vEmail));
                  $this->email->cc('bhavesh.khanpara@indianic.com');
                  $this->email->subject("$uid - (M)Your Plan will be auto subscribe for next 28 days");
                  $this->email->message($message);
                  $this->email->send();

                }

               //Send Mail if payment fail
               if($payment_status != 'Completed'){
                  $this->email->from($this->config->item('mail_from'), 'PAYMENT FAIL');
                  $this->email->to(trim($userDetails->vEmail));
                  $this->email->subject("$uid - PAYMENT FAIL");
                  $this->email->message('You are inactive by Our system dur to Auto subscription Issue.');
                  $this->email->send();
               }
            }
            else if($_REQUEST['txn_type'] == 'recurring_payment'){
              $recurring_payment_id = $_REQUEST['recurring_payment_id'];
              //get user info
              $userIndividual_user_info = $this->commonmodel->get_item_by_paymentId($recurring_payment_id);
              $userId = $userIndividual_user_info->iUserId;
              $userPlanId = $this->commonmodel->get_planId_by_iUserId('business',$userId);
              if(@$userId != ''){
                /*Update Payment Table*/
                @$payment_amount    = $_REQUEST['mc_gross'];
                @$iPlanPeriod       = '28';
                @$dPlanStartDate    = date('Y-m-d H:i:s');
                @$dPlanExpiryDate   = date("Y-m-d H:i:s",strtotime("+28 day", strtotime(@$dPlanStartDate)));
                @$eSubscription_mode = 'Inactive';

                /*  Update Business details*/
                $BuisnessData = array('dPlanStartDate' => $dPlanStartDate);
                if(@$payment_status=='Completed'){
                    $eSubscription_mode = 'Active';
                    $BuisnessData['dPlanExpiryDate']    = $dPlanExpiryDate;
                    $BuisnessData['eSubscription_mode'] = $eSubscription_mode;
                }
                $this->db->set($BuisnessData);
                $this->db->where('iUserId',$userId);
                $this->db->update('business');
                /*  End business details */

                /*Update  plan_payment table*/
                $planTabledata = array(
                                    'iUserId'     =>  $userId,
                                    'iPlanId'     => $userPlanId,
                                    'iTrans_id'   => $txn_id,
                                    'fAmount'     => $payment_amount,
                                    'eCurrency'   => $ePriceType,
                                    'dCreated'    => date('Y-m-d'),
                                    'eStatus'     => 'Active'
                      );
                $this->commonmodel->insert('plan_payment',$planTabledata);

                /* Send mail */
                  /*$message = '"'.$userId.'" Recurring Second call payment is Now Done of user Id';
                  $this->email->from('bhavesh.khanpara@indianic.com', 'Auto Upgrade Plan paypal express checkout method');
                  $this->email->to($userIndividual_user_info->vEmail);
                  $this->email->subject("User $userId - Your Plan will be auto subscribe for next 28 days");
                  $this->email->message($message);
                  $this->email->send();*/

                  $this->data['paypal_recurring_transactionid'] = @$txn_id;
                  $this->data['content_message'] = 'This is to notify that your plan is sucessfully upgrade for next 28 days,please find below your payment transction id';

                  $message = $this->load->view('mail/subscription',$this->data,TRUE);
                  $this->email->initialize(array('mailtype' => 'html'));
                  $uid = $userId;
                  $this->email->from($this->config->item('mail_from'), ' App - Payment Recurring Notification');
                  $this->email->to($userIndividual_user_info->vEmail);
                  $this->email->cc('bhavesh.khanpara@indianic.com');
                  $this->email->subject("$uid - (A)Your Plan will be auto subscribe for next 28 days");
                  $this->email->message($message);
                  $this->email->send();
              }

              //Send Mail if payment fail
              if($payment_status != 'Completed'){
                $this->email->from($this->config->item('mail_from'), 'PAYMENT FAIL Express checkout');
                $this->email->to(trim($userIndividual_user_info->vEmail));
                $this->email->cc('bhavesh.khanpara@indianic.com');
                $this->email->subject("$uid - PAYMENT FAIL");
                $this->email->message('You are inactive by Our system dur to Auto subscription Issue.');
                $this->email->send();
              }
            }
            else if($_REQUEST['txn_type'] == 'subscr_cancel'){

                            $this->email->from($this->config->item('mail_from'), 'PAYMENT FAIL');
                            $this->email->to($userDetails->vEmail);
                            $this->email->subject("$uid - PAYMENT FAIL");
                            $this->email->message('subscr_cancel condition.');
                            $this->email->send();
            }
      }
      else if (strcmp ($res, "INVALID") == 0){
              $this->email->from($this->config->item('mail_from'), 'PAYMENT FAIL');
              $this->email->to($this->config->item('mail_from'));
              $this->email->subject('PAYMENT FAIL');
              $this->email->message('Fail Processing.');
              $this->email->send();
      }
	  }
  }
}


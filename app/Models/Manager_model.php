<?php
/* Update ver1.0 */
namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

class Manager_model extends Model
{
    public function __construct(?ConnectionInterface $db = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($db, $validation);
    }
    public function process_payment($param) {
        $retval['status'] = true;
        $retval['cur_yr'] = 0;
        $retval['pay_type'] = 0;

            $mem_arr = $this->get_member($param['email']);

            if ($mem_arr === null) {
                return [
                    'status' => false,
                    'message' => 'Member was not found for the submitted email address.',
                ];
            }

            $cur_yr = $mem_arr['cur_year'];

            $cur_mo = intval(date('m', time()));
            $year_now = intval(date('Y', time()));

            if($cur_yr < date('Y', time())) {
                if( intval(date('m', time())) > 9 && intval(date('m', time())) <= 12) {
                    $cur_yr = $year_now + 1;
                }
                else {
                    $cur_yr = $year_now;
                }
            }
            else  {
                $cur_yr++;
            } 
            
            $retval['cur_yr'] = $cur_yr;
            $retval['mem'] = $mem_arr;

            $paydata = $this->get_paydata();
            $valstr = $this->guid();
            $time_stamp = time() - (3 * 3600);
            $retval['time_stamp'] = $time_stamp;
            $carr_paid = 0;
            $mem_paid = 0;
            $don_paid = 0;
            $don_rep_paid = 0;
            $total_fee = number_format($param['total'] * $paydata['trans_per'] + $paydata['trans_fee'], 2);

            $trans_arr = array('id_member' => $mem_arr['id_members'], 'total_amt' => $param['total'], 'fee_amt' => $total_fee, 'date' => $time_stamp);
            $this->db->table('transactions')->insert($trans_arr);
            $last_trans = $this->db->insertID();


            if($param['carrier'] == 'carrier') {
                $dbdata = array(
                    'id_member' => $mem_arr['id_members'],
                    'id_payaction' => 10,
                    'id_entity' => 2,
                    'amount' => $paydata['carrier'],
                    'paydate' => time(),
                    'result' => 'success',
                    'val_string' => $valstr,
                    'flag' => 0,
                    'for_year' => $cur_yr,
                    'id_transaction' => $last_trans
                );
                $this->db->table('mem_payments')->insert($dbdata);
                $carr_paid = $paydata['carrier'];
            }

            if($param['membership'] == 'mem') {
                
                $idpay = 0;
                if($param['student'] == 'on') {
                    $mem_paid = $paydata['student_amt'];
                    $idpay = 16;
                    $retval['pay_type'] = 16;
                }
                else {
                    $mem_paid = $paydata['membership'];
                    $idpay = 1;
                    $retval['pay_type'] = 1;
                }
                
                $dbdata = array(
                    'id_member' => $mem_arr['id_members'],
                    'id_payaction' => $idpay,
                    'id_entity' => 2,
                    'amount' => $mem_paid,
                    'paydate' => time(),
                    'result' => 'success',
                    'val_string' => $valstr,
                    'flag' => 0,
                    'for_year' => $cur_yr,
                    'id_transaction' => $last_trans
                );
                $this->db->table('mem_payments')->insert($dbdata);

                $dbdata2 = array('paym_date' => $time_stamp, 'cur_year' => $cur_yr);
                $this->db->table('tMembers')
                    ->where('id_members', $mem_arr['id_members'])
                    ->update($dbdata2);

                $this->db->table('tMembers')
                    ->where('parent_primary', $mem_arr['id_members'])
                    ->update($dbdata2);
            }

            //echo 'don mdarc: ' . $param['donation'] . '<br>';
            //echo 'don in model - before if: ' . $param['donation'] . '<br>';
            if(intval($param['donation']) > 0) {
                //$don_paid = $param['total'] - ($mem_paid + $paydata['carrier']);
                $don_paid = $param['donation'];
                //echo 'don in model - in if: ' . $don_paid . '<br>';
                $dbdata = array(
                    'id_member' => $mem_arr['id_members'],
                    'id_payaction' => 7,
                    'id_entity' => 2,
                    'amount' => $don_paid,
                    'paydate' => time(),
                    'result' => 'success',
                    'val_string' => $valstr,
                    'flag' => 0,
                    'for_year' => $cur_yr,
                    'id_transaction' => $last_trans
                );
                $this->db->table('mem_payments')->insert($dbdata);
            }            

            if(intval($param['don_rep']) > 0) {
                //$don_paid = $param['total'] - ($mem_paid + $paydata['carrier']);
                $don_rep_paid = $param['don_rep'];
                //echo 'don in model - in if: ' . $don_paid . '<br>';
                //echo 'don rep:' . $don_rep_paid;
                $dbdata = array(
                    'id_member' => $mem_arr['id_members'],
                    'id_payaction' => 5,
                    'id_entity' => 2,
                    'amount' => $don_rep_paid,
                    'paydate' => time(),
                    'result' => 'success',
                    'val_string' => $valstr,
                    'flag' => 0,
                    'for_year' => $cur_yr,
                    'id_transaction' => $last_trans
                );
                $this->db->table('mem_payments')->insert($dbdata);
            }

            //$email['to'] = array('jkulisek.us@gmail.com', 'mdarc-memberships@arrleb.org');
			$email['to'] = array('bwhysong@gmail.com', 'mdarc-memberships@arrleb.org');
			$email['subject'] = 'MDARC Payment ' . $mem_arr['fname'] . ' ' . $mem_arr['lname'];
			$email['message'] = '<h2>Payment to Stripe Account Completed</h2>
								<p>Payment date: '. date('F j, Y, g:i a', $time_stamp) . '<br>
								Payment for MDARC member: ' .  $mem_arr['fname'] . " ". $mem_arr['lname'] . '<br>
								Email: '. $param['email'] . '<br>
								Membership amount: $'. number_format($mem_paid, 2, '.', ',') . " (Current year " . $cur_yr . ' )' . '<br>
								MDARC donation amount $'. number_format($don_paid, 2, '.', ',') . '<br>
								Repeater donation amount $'. number_format($don_rep_paid, 2, '.', ',') . '<br>
								The Carrier amount $' . number_format($carr_paid, 2, '.', ',') . '<br>
								-------------------------------------------------------<br>
								<strong>Total amount: $' . number_format($param['total'], 2, '.', ',') . '</strong><br><br>
								Sent via MDARC Payment Gateway';
			$this->send_email($email);

			/* --------------------------------------------*/

			$email['to'] = array($param['email']);
            $email['subject'] = 'MDARC Payment: Thank You!';
			$email['message'] = '<h2>MDARC Membership Payment Completed - Thank You!</h2>
								<p>Payment date: '. date('F j, Y, g:i a', $time_stamp) . '<br>
								Payment for MDARC member: ' .  $mem_arr['fname'] . " ". $mem_arr['lname'] . '<br>
								Email: '. $param['email'] . '<br>
								Membership amount: $'. number_format($mem_paid, 2, '.', ',') . " (Current year " . $cur_yr . ' )' . '<br>
								Donation amount $'. number_format($don_paid, 2, '.', ',') . '<br>
								Repeater donation amount $'. number_format($don_rep_paid, 2, '.', ',') . '<br>
								The Carrier amount $' . number_format($carr_paid, 2, '.', ',') . '<br>
								-------------------------------------------------------<br>
								<strong>Total amount: $' . number_format($param['total'], 2, '.', ',') . '</strong><br><br>
								Sent via MDARC Payment Gateway';

			$this->send_email($email);
            
            $retval['mem_amount'] = $mem_paid;
            $retval['don_amount'] = $don_paid;
            $retval['don_rep_amount'] = $don_rep_paid;
            $retval['carr_amount'] = $carr_paid;
            $retval['total'] = $param['total'];
        
        return $retval;
    }

    private function send_email($email) {
        $mail = service('email');
        $mail->initialize([
            'protocol' => 'smtp',
            'SMTPHost' => 'smtp.ionos.com',
            'SMTPUser' => env('mdarc_email_usr'),
            'SMTPPass' => env('mdarc_email_pass'),
            'SMTPPort' => 587,
            'SMTPCrypto' => 'tls',
            'mailType' => 'html',
            'charset' => 'UTF-8',
        ]);

        $mail->setFrom('mdarc-memberships@arrleb.org', 'MDARC Membership Chair');
        $mail->setReplyTo('mdarc-memberships@arrleb.org', 'MDARC Membership Chair');
        $mail->setTo($email['to']);
        $mail->setSubject($email['subject']);
        $mail->setMessage($email['message']);

        if (! $mail->send()) {
            log_message('error', 'MDARC email failed: {debug}', [
                'debug' => $mail->printDebugger(['headers']),
            ]);
        }
	}

    public function get_paydata() {
        $retarr = array();
        $res = $this->db->table('payactions')
            ->select('amount')
            ->where('id_payaction', 1)
            ->get()
            ->getRow();
        $retarr['membership'] = $res->amount;

        $res = $this->db->table('payactions')
            ->select('amount')
            ->where('id_payaction', 10)
            ->get()
            ->getRow();
        $retarr['carrier'] = $res->amount;

        $res = $this->db->table('payactions')
            ->select('amount')
            ->where('id_payaction', 16)
            ->get()
            ->getRow();
        $retarr['student_amt'] = $res->amount;

        $res = $this->db->table('payactions')
            ->select('amount')
            ->where('id_payaction', 17)
            ->get()
            ->getRow();
        $retarr['trans_fee'] = $res->amount;

        $res = $this->db->table('payactions')
            ->select('amount')
            ->where('id_payaction', 18)
            ->get()
            ->getRow();
        $retarr['trans_per'] = $res->amount;

        return $retarr;
    }

    public function check_email($email) {
        return $this->db->table('tMembers')
            ->where('email', $email)
            ->countAllResults() > 0;
    }

    public function get_member($email) {
        $res = $this->db->table('tMembers')
            ->where('email', $email)
            ->get()
            ->getRow();

        if ($res === null) {
            return null;
        }

        $retarr['id_members'] = $res->id_members;
        $retarr['fname'] = $res->fname;
        $retarr['lname'] = $res->lname;
        $retarr['callsign'] = $res->callsign;
        $retarr['email'] = $email;
        $retarr['cur_year'] = $res->cur_year;
        $retarr['line1'] = $res->address;
        $retarr['city'] = $res->city;
        $retarr['state'] = $res->state;
        $retarr['postal_code'] = $res->zip;
        return $retarr;
    }

    public function check_student($email) {
        return $this->db->table('tMembers')
            ->where('email', $email)
            ->where('id_mem_types', 5)
            ->countAllResults() === 0;
    }

    private function guid() {
        if (function_exists('com_create_guid') === true)
            return trim(com_create_guid(), '{}');
        
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

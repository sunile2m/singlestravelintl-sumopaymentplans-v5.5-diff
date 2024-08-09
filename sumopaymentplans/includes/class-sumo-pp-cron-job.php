<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Payment cron jobs.
 * 
 * @class SUMO_PP_Cron_Job
 * @category Class
 */
class SUMO_PP_Cron_Job extends SUMO_PP_Abstract_Cron_Job {

    /**
     * Schedule Balance payment Order Creation.
     * @param string $next_payment_date
     * @return boolean true on success
     */
    public function schedule_balance_payable_order( $next_payment_date ) {
        //Check whether to Schedule this Cron.
        if( ! apply_filters( 'sumopaymentplans_schedule_payment_cron_job' , true , 'create_balance_payable_order' , $this->payment->id ) ) {
            return ;
        }

        $next_payment_cycle_days = _sumo_pp_get_payment_cycle_in_days( null , null , $next_payment_date ) ;
        $no_of_days_before       = absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'create_next_payable_order_before' , '1' ) ) ;

        if( $next_payment_cycle_days < $no_of_days_before ) {
            $no_of_days_before = $next_payment_cycle_days ;
        }

        //Get Timestamp for next balance payable order to be Happened.
        $timestamp = _sumo_pp_get_timestamp( "$next_payment_date -$no_of_days_before days" ) ;

        return $this->create_job( $timestamp , 'create_balance_payable_order' , array(
                    'next_payment_on' => $next_payment_date ,
                ) ) ;
    }

    /**
     * Schedule Payment Reminders.
     * @param int $balance_payable_order_id The Order post ID
     * @param string $remind_before
     * @param string $mail_template_id
     * @return boolean true on success
     */
    public function schedule_reminder( $balance_payable_order_id , $remind_before , $mail_template_id ) {
        //Check whether to Schedule this Cron.
        if( ! apply_filters( 'sumopaymentplans_schedule_payment_cron_job' , true , 'notify_reminder' , $this->payment->id ) ) {
            return ;
        }

        $reminder_intervals       = _sumo_pp_get_reminder_intervals( $mail_template_id ) ;
        $remind_before_time       = _sumo_pp_get_timestamp( $remind_before ) ;
        $remind_from_time         = _sumo_pp_get_timestamp( get_post( $balance_payable_order_id )->post_date_gmt ) ;
        $available_days_to_notify = ceil( ($remind_before_time - $remind_from_time) / 86400 ) ;

        $notifications = array() ;
        foreach( $reminder_intervals as $notify_day ) {
            if( $notify_day && $available_days_to_notify >= $notify_day ) {
                $notifications[] = absint( $remind_before_time - (86400 * $notify_day) ) ;
            }
        }

        $scheduled     = false ;
        if( $notifications = array_unique( $notifications ) ) {
            foreach( $notifications as $notification_time ) {
                if( $notification_time >= _sumo_pp_get_timestamp( 0 , 0 , true ) && $this->create_job( $notification_time , 'notify_reminder' , array(
                            'balance_payable_order_id' => absint( $balance_payable_order_id ) ,
                            'mail_template_id'         => $mail_template_id
                        ) )
                ) {
                    $scheduled = true ;
                }
            }
        }

        if( ! $scheduled ) {
            $scheduled = $this->create_job( _sumo_pp_get_timestamp() , 'notify_reminder' , array(
                'balance_payable_order_id' => absint( $balance_payable_order_id ) ,
                'mail_template_id'         => $mail_template_id
                    ) ) ;
        }
        return $scheduled ;
    }

    /**
     * Schedule Overdue Payment
     * @param int $balance_payable_order_id The Order post ID
     * @param string | int $next_payment_date
     * @param string | int $overdue_date_till
     * @return boolean true on success
     */
    public function schedule_overdue_notify( $balance_payable_order_id , $next_payment_date , $overdue_date_till ) {
        //Check whether to Schedule this Cron.
        if( ! apply_filters( 'sumopaymentplans_schedule_payment_cron_job' , true , 'notify_overdue' , $this->payment->id ) ) {
            return ;
        }

        return $this->create_job( _sumo_pp_get_timestamp( $next_payment_date ) , 'notify_overdue' , array(
                    'balance_payable_order_id' => absint( $balance_payable_order_id ) ,
                    'overdue_date_till'        => _sumo_pp_get_timestamp( $overdue_date_till ) ,
                ) ) ;
    }

    /**
     * Schedule Cancelled Payment
     * @param int $balance_payable_order_id The Renewal Order post ID
     * @param string | int $payment_closing_date
     * @return boolean true on success
     */
    public function schedule_cancelled_notify( $balance_payable_order_id , $payment_closing_date ) {
        //Check whether to Schedule this Cron.
        if( ! apply_filters( 'sumopaymentplans_schedule_payment_cron_job' , true , 'notify_cancelled' , $this->payment->id ) ) {
            return ;
        }

        return $this->create_job( _sumo_pp_get_timestamp( $payment_closing_date ) , 'notify_cancelled' , array(
                    'balance_payable_order_id' => absint( $balance_payable_order_id ) ,
                ) ) ;
    }

}

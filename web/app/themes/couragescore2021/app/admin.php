<?php

namespace App;

/**
 * Theme customizer
 */
add_action('customize_register', function (\WP_Customize_Manager $wp_customize) {
    // Add postMessage support
    $wp_customize->get_setting('blogname')->transport = 'postMessage';
    $wp_customize->selective_refresh->add_partial('blogname', [
        'selector' => '.brand',
        'render_callback' => function () {
            bloginfo('name');
        }
    ]);
});

/**
 * Customizer JS
 */
add_action('customize_preview_init', function () {
    wp_enqueue_script('sage/customizer.js', asset_path('scripts/customizer.js'), ['customize-preview'], null, true);
});

/***************************** */
// Show the Chamber in admin menu
/***************************** */
/*
 * Add columns to exhibition post list
 */
function add_acf_columns ( $columns ) {
    return array_merge ( $columns, array ( 
      'senate_or_assembly' => __ ( 'Chamber' ),
      'party'   => __ ( 'Party' ) 
    ) );
  }
add_filter ( 'manage_people_posts_columns', __NAMESPACE__ . '\\add_acf_columns' );
/*
 * Add columns to exhibition post list
 */
function people_custom_column ( $column, $post_id ) {
    switch ( $column ) {
      case 'senate_or_assembly':
            $chamber = get_field ( 'senate_or_assembly', $post_id );
            $district = get_field ( 'district', $post_id );
            if($district){
                echo $chamber . ' district ' . $district;
            } else {
                echo 'No District';
            }
        break;
        case 'party':
            echo get_field ( 'party', $post_id );
        break;
    }
  }
add_action ( 'manage_people_posts_custom_column', __NAMESPACE__ . '\\people_custom_column', 10, 2 );



/***********/
// BILLS
/***********/

/*** Get New Bills ***/
function get_scorecards(){
    
    /*** API Request ***/ 
    $authorization = "Authorization: apikey " . BILLTRACK_API;
    $url = 'https://www.billtrack50.com/BT50Api/2.0/json/Scorecards';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);

    curl_close($ch);
    $response_a = json_decode($response);
   // print_r($response_a);
   // die();
    return  $response_a;
    
}


function get_bills_by_scorecard(){
     set_time_limit(0);
    /*** API Request ***/ 
    $authorization = "Authorization: apikey " . BILLTRACK_API;
    
    $url = 'https://www.billtrack50.com/BT50Api/2.0/json/Scorecards/'. $_GET['scorecardID'] .'/Bills';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);

    curl_close($ch);
    $response_a = json_decode($response);

    $bills = $response_a->bills;
    //$bill_list_full = $bill_list_full + $bills;

    wp_send_json_success($bills);
}
add_action('wp_ajax_get_bills_by_scorecard', __NAMESPACE__ .'\\get_bills_by_scorecard' );

function get_bill_summary($bill_id){
    /*** API Request ***/ 
    $authorization = "Authorization: apiKey " . BILLTRACK_API;
    $url = 'https://www.billtrack50.com/bt50api/2.0/json/bills/' . $bill_id;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);

    curl_close($ch);
    $response_a = json_decode($response);

    $bill_summary = $response_a->bill->summary;
    return  $bill_summary;
}

/*******************/
// GET VOTES PER BILL
/*******************/


function get_bill_votes($bill_id){
    /*** API Request ***/ 
    $authorization = "Authorization: apikey " . BILLTRACK_API;
    $url = 'https://www.billtrack50.com/BT50Api/2.0/json/Bills/' . $bill_id . '/Votes';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);

    curl_close($ch);
    $response_a = json_decode($response);
//print_r( $response_a);
    $votes_arr = $response_a->votes;
    //return $response_a;
    return $votes_arr;
}

/*******************/
// UPDATE BILLS
/*******************/

function update_bill(){

$bill = $_GET['bill'];

// Find out if bill is in the DB
$bills_query = new \WP_Query(array(
    'post_type'         => 'vote',
    'post_status'       => array('draft', 'publish'),
    'meta_query'        => array(
        array(
            'key' => 'billtrack_id',
            'value' => $bill['billID'],
            'compare' => '=',
        )
    )
));
$wp_bill = $bills_query->posts[0];

// Create post if bill doesn't exist
if ( !$bills_query->have_posts()){
    // Create post object
    $bill_summary = get_bill_summary($bill['billID']);
    
    $new_bill = array(
        'post_title'    => $bill['stateBillID'],
        'post_content'  => $bill_summary,
        'post_status'   => 'draft',
        'post_author'   => 1,
        'post_type' => 'vote',
    );
    $new_post_ID = wp_insert_post( $new_bill );
    update_field('title', $bill['billName'],  $new_post_ID );
    update_field('billtrack_id', $bill['billID'],  $new_post_ID );
    update_field('last_update_attempt_ts', 0,  $new_post_ID );
    update_field('last_update_success_ts', 0,  $new_post_ID );

    // Get post from database
    $wp_bill_query = new \WP_Query(array(
        'post_type'     => 'vote',
        'p'            => $new_post_ID
    ));
    $wp_bill = $wp_bill_query->post;
}

//Check that we didn't try to update it in the last 10 hours
$limitts = time() - 10*60*60; //-10 hours
$a_ts = get_field("last_update_attempt_ts", $wp_bill->ID);
$s_ts = get_field("last_update_success_ts", $wp_bill->ID);
$billtrack_id = get_field("billtrack_id", $wp_bill->ID);
$voting_history = get_field('voting_history', $wp_bill->ID);

if(empty($billtrack_id) || $a_ts > $limitts){
    wp_send_json_error('Already updated');
}

update_field("last_update_attempt_ts", time(), $wp_bill->ID);


//Get votes
$votes = get_bill_votes($billtrack_id);

//Get current vote IDs
$voting_history_arr = array();
if(have_rows('voting_history', $wp_bill->ID)): 
    while(have_rows('voting_history', $wp_bill->ID)): the_row();
        $voting_history_arr[] = get_sub_field('vote_id');
    endwhile; 
endif;

$updatedVotes = 0;

if($votes){
    foreach ($votes as $vote) {
        $vote_id = $vote->voteID;
        if(!in_array($vote_id, $voting_history_arr)){
            $updatedVotes++;
            $vote_date = date("Ymd", strtotime($vote->voteDate));
            $votes_count = $vote->yesVotes + $vote->noVotes + $vote->otherVotes;
            if( $votes_count>37 ){ $commitee_floor = 'floor'; } else {$commitee_floor = 'committee';};
           
            $row = array(
                'field_5a5f6d4a0ae1a' =>  $vote_id,
                'field_5a5f6d5c0ae1b' =>  $vote_date,
                'field_5a5f6d910ae1c' =>  $commitee_floor,
                'field_5a61f5744032d' =>  $vote->yesVotes,
                'field_5a61f58b4032e' =>  $vote->noVotes,
                'field_5a61f5934032f' =>  $vote->otherVotes
            );
       
            $i = add_row('field_5a5f6d320ae19', $row, $wp_bill->ID );
            unset($row);
            unset($vote_id);
            unset($vote_date);
            unset($commitee_floor);
        }
    }           
}
unset($voting_history_arr);
update_field("last_update_success_ts", time(), $wp_bill->ID);

wp_send_json_success($updatedVotes . 'votes updated');
}
add_action('wp_ajax_update_bill', __NAMESPACE__ .'\\update_bill' );
<?php
/**
 * Created by PhpStorm.
 * User: einarvalur
 * Date: 1/22/14
 * Time: 8:54 PM
 */

$date0 = new DateTime();
    $date0->add( new DateInterval('P1M') );
$date1 = new DateTime();
$date2 = new DateTime();
    $date2->sub( new DateInterval('P1M') );
$date3 = new DateTime();
    $date3->sub( new DateInterval('P2M') );
$date4 = new DateTime();
    $date4->sub( new DateInterval('P3M') );
$date5 = new DateTime();
    $date5->sub( new DateInterval('P4M') );
$date6 = new DateTime();
    $date6->sub( new DateInterval('P5M') );

return [
    'Group' => [
        [ 'id'=>1, 'name'=>'name1', 'name_short'=>'n1', 'description'=>'', 'objective'=>'', 'what_is'=>'', 'how_operates'=>'', 'for_whom'=>'', 'url'=>'n1' ],
        [ 'id'=>2, 'name'=>'name2', 'name_short'=>'n2', 'description'=>'', 'objective'=>'', 'what_is'=>'', 'how_operates'=>'', 'for_whom'=>'', 'url'=>'n2' ],
        [ 'id'=>3, 'name'=>'name3', 'name_short'=>'n3', 'description'=>'', 'objective'=>'', 'what_is'=>'', 'how_operates'=>'', 'for_whom'=>'', 'url'=>'n3' ],
        [ 'id'=>4, 'name'=>'name4', 'name_short'=>'n4', 'description'=>'', 'objective'=>'', 'what_is'=>'', 'how_operates'=>'', 'for_whom'=>'', 'url'=>'n4' ],
        [ 'id'=>5, 'name'=>'name4', 'name_short'=>'n4', 'description'=>'', 'objective'=>'', 'what_is'=>'', 'how_operates'=>'', 'for_whom'=>'', 'url'=>'n5' ],
    ],
    'User' => [
        ['id'=>1, 'name'=>'', 'passwd'=>'', 'email'=>'one@mail.com', 'title'=>'', 'created_date'=>date('Y-m-d H:i:s'), 'modified_date'=>date('Y-m-d H:i:s'), 'frequency'=>1, 'is_admin'=>1],
        ['id'=>2, 'name'=>'', 'passwd'=>'', 'email'=>'two@mail.com', 'title'=>'', 'created_date'=>date('Y-m-d H:i:s'), 'modified_date'=>date('Y-m-d H:i:s'), 'frequency'=>1, 'is_admin'=>0],
        ['id'=>3, 'name'=>'', 'passwd'=>'', 'email'=>'three@mail.com', 'title'=>'', 'created_date'=>date('Y-m-d H:i:s'), 'modified_date'=>date('Y-m-d H:i:s'), 'frequency'=>1, 'is_admin'=>0],

        ['id'=>4, 'name'=>'', 'passwd'=>'', 'email'=>'four@mail.com', 'title'=>'', 'created_date'=>date('Y-m-d H:i:s'), 'modified_date'=>date('Y-m-d H:i:s'), 'frequency'=>1, 'is_admin'=>0],
        ['id'=>5, 'name'=>'', 'passwd'=>'', 'email'=>'five@mail.com', 'title'=>'', 'created_date'=>date('Y-m-d H:i:s'), 'modified_date'=>date('Y-m-d H:i:s'), 'frequency'=>1, 'is_admin'=>0],
        ['id'=>6, 'name'=>'', 'passwd'=>'', 'email'=>'six@mail.com', 'title'=>'', 'created_date'=>date('Y-m-d H:i:s'), 'modified_date'=>date('Y-m-d H:i:s'), 'frequency'=>1, 'is_admin'=>0],
        ['id'=>7, 'name'=>'', 'passwd'=>'', 'email'=>'seven@mail.com', 'title'=>'', 'created_date'=>date('Y-m-d H:i:s'), 'modified_date'=>date('Y-m-d H:i:s'), 'frequency'=>1, 'is_admin'=>0],
        ['id'=>8, 'name'=>'', 'passwd'=>'', 'email'=>'eight@mail.com', 'title'=>'', 'created_date'=>date('Y-m-d H:i:s'), 'modified_date'=>date('Y-m-d H:i:s'), 'frequency'=>1, 'is_admin'=>0],
    ],
    'Group_has_User' => [
        [ 'group_id'=>1, 'user_id'=>1, 'type'=>2 ],
        [ 'group_id'=>2, 'user_id'=>1, 'type'=>1 ],
        [ 'group_id'=>2, 'user_id'=>2, 'type'=>0 ],
        [ 'group_id'=>2, 'user_id'=>3, 'type'=>0 ],

        [ 'group_id'=>5, 'user_id'=>1, 'type'=>2 ],
        [ 'group_id'=>5, 'user_id'=>2, 'type'=>2 ],
        [ 'group_id'=>5, 'user_id'=>3, 'type'=>1 ],
        [ 'group_id'=>5, 'user_id'=>4, 'type'=>1 ],
        [ 'group_id'=>5, 'user_id'=>5, 'type'=>1 ],
        [ 'group_id'=>5, 'user_id'=>6, 'type'=>0 ],
        [ 'group_id'=>5, 'user_id'=>7, 'type'=>0 ],
    ],
];
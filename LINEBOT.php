<?php
/**
 * Created by PhpStorm.
 * User: ethanlin
 * Date: 2018/9/26
 * Time: 下午4:11
 * Project: Line-bot-test
 */

class LINEBOT extends CI_Controller
{

    function __construct()
    {

        // Call the Model constructor
        parent::__construct();

        $this->load->model("page_model","",false);
        $this->load->model("city_model","",false);
        $this->load->model("area_model","",false);
        $this->load->model("vcash_model","",false); // 取得禮券用
        $this->load->model("return_money_model","",false); // 取得返還金用
        $this->load->model("store_settings_model","",false);//寄Email用到
        $this->load->model("register_code_model","",false);
        $this->load->model("doc_collection_keep_model","",false);
        $this->load->model("mem_model","",false);
        $this->load->model("orders_model","",false);
        $this->load->model("store_purchases_reserve_model","",false);
        $this->load->model("customers_collect_model","",false);
        $this->load->model('item_model','',false);
        $this->load->model("doc_collection_model","",false);
        $this->load->model("store_items_defined_model","",false);
        $this->load->model('email_model','',false);
        $this->load->model('keyword_model','',false);
        $this->load->model('store_logins_model','',false);
        $this->load->model('custom_fb_model','',false);
        $this->load->model('custom_customer_tagged_place_model','',false);
        $this->load->model('custom_store_place_mapping_model','',false);
        $this->load->model('custom_already_store_model','',false);
        $this->load->model('custom_want_store_model','',false);
        $this->load->model('store_cell_model','',false);
        $this->load->model("doc_collection_model","",FALSE);
        $this->load->model("bonus_model","",false);
        $this->load->model("custom_badge_model","",false);
        $this->load->model("custom_badge_stores_model","",false);
        $this->load->model('store_purchases_rank_model','',false);
        $this->load->model('country_model','',false);
        $this->load->model('topical_model','',false);
        $this->load->model('custom_signup_model','',false);
        $this->load->model('custom_waiting_list_model','',false);

        $this->load->helper('email');


        $this->load->library("net_util");
        $this->load->library("mitake"); //三竹簡訊

        $this->load->library('my_bonus');//註冊送購物金用

        $this->load->library('encrypt');

        // send mail需要的設定
        $this->mail_setting=$this->store_settings_model->select_by_id(STORE_ID);
        $config = Array(
            'smtp_host' => $this->mail_setting->Store_Mail_Server,
            //'smtp_user' => $mail_setting->Store_Mail_Id,
            //'smtp_pass' => $mail_setting->Store_Mail_Password,
            'charset'   => 'utf-8',
            'protocol' => 'smtp',
            'mailtype' =>'html'
        );
        //載入需要的library與model
        $this->load->library('email',$config);

        $fb_config=array("facebook_app_id"=>FBAPP_ID,"facebook_secret"=>FBAPP_SECRET,"fileUpload"=>False);
        $this->load->library("fb",$fb_config);


        //分頁基本設定
        $this->load->library('pagination');
        $this->pagintion['num_links'] = 5;
        $this->pagintion['use_page_numbers'] = TRUE;
        $this->pagintion['page_query_string'] = false;
        $this->pagintion = array_merge($this->pagintion, $this->net_util->page_sinyi_style());

        // 載入切圖相關的library
        $this->load->library('image_lib');
        $this->img['image_library'] = 'gd2';
        $this->img['maintain_ratio'] = TRUE;


        $this->load->library("line_api");
        $line_configs["client_id"] = LINE_CLIENT_ID;
        $line_configs["client_secret"] = LINE_CLIENT_SECRET;

        $this->line_api->configs($line_configs);

        $this->load->library("toyota_api");

    }

    public function test()
    {
        $access_token ='eIg2vWxVHRoDPt+kJefBhU3/UC2EyuinajhUCF7FWYyO40pm4Rpy2MRkIkMKFCEhhTBBrrXXEQChYYCzUdKw3LeQMrYmmpOcZ/KoDGJiGz4NN+O8YuhRZAVgf16gtc9fapptKzCIXEWhNFWQ+D57iwdB04t89/1O/w1cDnyilFU=';
        //define('TOKEN', '你的Channel Access Token');

        $json_string = file_get_contents('php://input');
        $file = fopen("/home/Line_log.txt", "a+");
        fwrite($file, $json_string."\n");
        $json_obj = json_decode($json_string);

        $event = $json_obj->{"events"}[0];
        $type  = $event->message->type;
        $messages = $event->{"message"}->{"text"};
        $reply_token = $event->{"replyToken"};


        switch ($type){

            case "text" :

                $cellphone_check = $this->config->item('encryption_key') . $messages;
                $member = $this->mem_model->select_store_customers_by_cell_phone_check(md5($cellphone_check));
                $cellphone = $this->encrypt->decode($member->CellPhone);

                if (preg_match("09[0-9]{2}-[0-9]{3}-[0-9]{3}", $cellphone) == 0 && (!empty($member))){

                    $post_data = [
                        "replyToken" => $reply_token,
                        "messages" => [
                            [
                                "type" => "text",
                                "text" => $cellphone . "您好，請輸入您的密碼"
                            ]
                        ]
                    ];
                }
                else{

                    $post_data = [
                        'replyToken' => $reply_token,
                        'messages' => [
                            [
                                'type' => 'template', // 訊息類型 (模板)
                                'altText' => 'Example buttons template', // 替代文字
                                'template' => [
                                    'type' => 'buttons', // 類型 (按鈕)
                                    'thumbnailImageUrl' => 'https://api.reh.tw/line/bot/example/assets/images/example.jpg', // 圖片網址 <不一定需要>
                                    'title' => 'Example Menu', // 標題 <不一定需要>
                                    'text' => "請輸入您的手機號碼以登入(09開頭且不含任何符號)"."\n"."尚未註冊會員請點選下方按鈕前往官網註冊", // 文字
                                    'actions' => [
                                        [
                                            'type' => 'uri', // 類型 (連結)
                                            'label' => '前往官網註冊', // 標籤 3
                                            'uri' => 'https://app.easycamp.com.tw/member/registration' // 連結網址
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                };
                break;
            /*------------------------回覆訊息寫入log----------------------------------
                            $result=print_r($post_data["messages"][0]["text"], true);
                            fwrite($file, $result);
            -------------------------------------------------------------------------*/

            case "image" :

                $post_data = [
                    "replyToken" => $reply_token,
                    "messages" => [
                        [
                            "type" => "text",
                            "text" => "目前僅接受文字輸入，暫不支援辨識圖片"
                        ]
                    ]
                ];
                break;

            case "video" :

                $post_data = [
                    "replyToken" => $reply_token,
                    "messages" => [
                        [
                            "type" => "text",
                            "text" => "目前僅接受文字輸入，暫不支援辨識影片"
                        ]
                    ]
                ];
                break;

            case "audio" :

                $post_data = [
                    "replyToken" => $reply_token,
                    "messages" => [
                        [
                            "type" => "text",
                            "text" => "目前僅接受文字輸入，暫不支援辨識聲音"
                        ]
                    ]
                ];
                break;

            case "location" :

                $post_data = [
                    "replyToken" => $reply_token,
                    "messages" => [
                        [
                            "type" => "text",
                            "text" => "目前僅接受文字輸入，暫不支援辨識位置"
                        ]
                    ]
                ];
                break;

            case "sticker":

                $post_data = [
                    "replyToken" => $reply_token,
                    "messages" => [
                        [
                            "type" => "sticker",
                            "packageId" =>"1",
                            "stickerId" => "403"
                        ]
                    ]
                ];
                break;
        }

        fwrite($file, json_encode($post_data)."\n");

        $ch = curl_init("https://api.line.me/v2/bot/message/reply");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$access_token
            //'Authorization: Bearer '. TOKEN
        ));
        $result = curl_exec($ch);
        fwrite($file, $result."\n");
        fclose($file);
        curl_close($ch);
    }

}

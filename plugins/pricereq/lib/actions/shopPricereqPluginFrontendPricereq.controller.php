<?php

/*
 * Class shopPricereqPluginFrontendPricereqController
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopPricereqPluginFrontendPricereqController extends waJsonController {

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(array('shop', 'pricereq'));

        $product_id = htmlspecialchars( waRequest::post('product_id', '', 'int') );
        $name       = htmlspecialchars( waRequest::post('name', '', 'str') );
        $phone      = htmlspecialchars( waRequest::post('phone', '', 'str') );
        $email      = htmlspecialchars( waRequest::post('email', '', 'str') );
        $comment    = htmlspecialchars( waRequest::post('comment', '', 'str') );

        if ( isset($settings['status']) && $settings['status'] === 'on' && (!empty($phone) || !empty($email)) ) {

            $model = new shopPricereqPluginRequestModel();

            $data = array(
                'contact_id'      => wa()->getUser()->getId(),
                'product_id'      => $product_id,
                'name'            => $name,
                'phone'           => $phone,
                'email'           => $email,
                'comment'         => $comment,
                'status'          => 'new',
                'create_datetime' => date('Y-m-d H:i:s'),
            );

            $model->insert($data);

            $product = new shopProduct($product_id);
            $product['frontend_url'] = wa()->getRouteUrl('shop/frontend/product', array(
                'product_url' => $product['url'],
            ), true);

            if (!$settings['email_of_sender']) { $settings['email_of_sender'] = wa('shop')->getConfig()->getGeneralSettings("email"); }
            if (!$settings['email_of_recipient']) { $settings['email_of_recipient'] = wa('shop')->getConfig()->getGeneralSettings("email"); }
            
            $subject = _wp('Price request');
            $body = "<h1>" . _wp('Good day!') . "</h1>";
            $body .= "<p>" . _wp('Customer') . " <b>" . $name ."</b> " . _wp('ordered a price request about a product') . " <a href='" . $product['frontend_url'] . "'><b>" . $product['name'] . "</b></a></p>";
            if ($phone) {
                $body .= "<p>" . _wp('Phone number') . ": <a href='tel:" . $phone . "'><b>" . $phone ."</b></a></p>";
            }
            if ($email) {
                $body .= "<p>" . _wp('E-mail') . ": <b>" . $email ."</b></p>";
            }
            if ($comment) {
                $body .= "<p>" . _wp('Comment') . ":<br /> <i>" . $comment ."</i></p>";
            }

            $mail_message = new waMailMessage();
            $mail_message->setBody($body);
            $mail_message->setSubject($subject);
            $mail_message->setFrom($settings['email_of_sender'], _wp('Price request plugin'));
            $mail_message->setTo($settings['email_of_recipient'], _wp('Administrator'));

            if ($mail_message->send()) {

                $this->response = array(
                    'status' => true,
                    'name' => $name,
                );

            } else {

                $this->response = array(
                    'status' => false,
                );
                
            }

        } else {

            $this->response = array(
                'status' => false,
            );

        }
    }  
     
}
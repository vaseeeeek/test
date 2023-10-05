<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/16/16
 * Time: 5:57 AM
 */
class shopInvitePluginBackendConfirmController extends waJsonController
{
    public function execute()
    {
        if (wa()->getUser()->getRights('shop', 'orders')) {
            $invitations_model = new shopInvitePluginInvitationsModel();
            $code = waRequest::post('code');
            $invite = $invitations_model->getById($code);

            if (!empty($invite)) {
                if ($invite['confirmed'] == 0) {
                    $invite['confirmed'] = 1;
                }
                else {
                    $invite['confirmed'] = 0;
                }
                $invitations_model->updateById($invite['code'], $invite);
            }
        }
        else {
            $this->setError(_wp('Access denied'));
        }
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/4/16
 * Time: 8:59 PM
 */

return array(
    'enable' => array(
        'value'         => 0,
        'title'         => _wp("Enable Invite Engine"),
        'description'   => _wp('If checked, then Invite Engine will be enabled.'),
        'control_type'  => waHtmlControl::CHECKBOX,
        'subject'       => 'basic_settings'
    ),
    'only_invite' => array(
        'value'         => 0,
        'title'         => _wp("Register by invitation only"),
        'description'   => _wp('If checked, it will be possible to register by invitation only.'),
        'control_type'  => waHtmlControl::CHECKBOX,
        'subject'       => 'basic_settings'
    ),
    'send' => array(
        'value'         => 0,
        'title'         => _wp("Send invitation by e-mail"),
        'description'   => _wp('If checked, invitation will be sended by e-mail.'),
        'control_type'  => waHtmlControl::CHECKBOX,
        'subject'       => 'basic_settings'
    ),
    'subj' => array(
        'value'         => _wp('Invitation'),
        'title'         => _wp("Invitation subject"),
        'description'   => _wp('Set the invitation subject for e-mail.'),
        'control_type'  => 'text',
        'subject'       => 'basic_settings'
    ),
    'log' => array(
        'value'         => 0,
        'title'         => _wp("Log sended e-mails"),
        'description'   => _wp('If checked, sended e-mails will be logged.'),
        'control_type'  => waHtmlControl::CHECKBOX,
        'subject'       => 'basic_settings'
    ),
    'domains'        => array(
        'title'         => _wp('Domains'),
        'description'   => _wp('Select the domain, for the work of invitations available.'),
        'control_type'  => waHtmlControl::GROUPBOX,
        'options_callback' => array('shopInvitePlugin', 'getDomainsControl'),
        'subject'       => 'basic_settings'
    ),
    'domain'        => array(
        'title'         => _wp('Domain for mail invitations'),
        'description'   => _wp('Select the domain to be used in messages.'),
        'control_type'  => waHtmlControl::RADIOGROUP,
        'options_callback' => array('shopInvitePlugin', 'getDomainsControl'),
        'subject'       => 'basic_settings'
    ),
    'categories' => array(
        'title'         => _wp("User Categories"),
        'description'   => _wp('Categories of users who are allowed to invite other users.'),
        'control_type'  => waHtmlControl::CUSTOM.' '.'shopInvitePlugin::getCategoriesControl',
        'subject'       => 'basic_settings'
    ),
    'templates' => array(
        'title' => _wp('Templates'),
        'value' => '',
        'control_type' => waHtmlControl::CUSTOM.' '.'shopInvitePlugin::getTemplatesControl',
        'subject'       => 'templates_settings',
    ),
    'feedback' => array(
        'title'         => _wp('Ask for technical support'),
        'description'   => _wp('Click on the link to contact the developer.'),
        'control_type'  => waHtmlControl::CUSTOM.' '.'shopInvitePlugin::getFeedbackControl',
        'subject'       => 'info_settings',
    ),
);
//EOF
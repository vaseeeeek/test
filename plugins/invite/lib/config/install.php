<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/19/16
 * Time: 6:47 PM
 */

try {
    $invite_code_field = new waContactStringField(
        'invite_code',
        array(
            'en_US'=>'Invitation code',
            'ru_RU'=>'Код приглашения'
        ),
        array(
            'app_id' => 'shop.invite',
            'required' => false,
            'allow_self_edit' => true,
            'unique' => true,
        )
    );
    waContactFields::updateField($invite_code_field);
    waContactFields::enableField($invite_code_field, 'person');
} catch (waException $ex) {
    // Что-то делать, если что-то пошло не так
}
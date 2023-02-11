<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle;

final class Events
{
    /**
     * Dispatched after the new token and new refresh token stored in database
     *
     * @Event("App\Event\TokenRefreshSuccessEvent")
     */
    public const TOKEN_REFRESH_SUCCESS = 'ifrost_doctrine_api_authentication.on_token_refresh_success';

    /**
     * Dispatched after raw user data has been taken from database for refresh token action response
     *
     * @Event("App\Event\TokenRefreshAfterGetUserDataEvent")
     */
    public const TOKEN_REFRESH_AFTER_GET_USER_DATA = 'ifrost_doctrine_api_authentication.on_token_refresh_after_get_user_data';
}

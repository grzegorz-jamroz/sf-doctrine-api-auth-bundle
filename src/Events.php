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

}

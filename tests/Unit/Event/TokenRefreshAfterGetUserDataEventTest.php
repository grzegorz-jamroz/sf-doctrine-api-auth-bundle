<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit\Event;

use Ifrost\DoctrineApiAuthBundle\Event\TokenRefreshAfterGetUserDataEvent;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\User;
use PHPUnit\Framework\TestCase;

class TokenRefreshAfterGetUserDataEventTest extends TestCase
{
    public function testShouldGetEntityClassName()
    {
        // Given
        $event = new TokenRefreshAfterGetUserDataEvent(User::class, []);

        // When & Then
        $this->assertEquals(
            User::class,
            $event->getEntityClassName(),
        );
    }

    public function testShouldSetData()
    {
        // Given
        $userData = [
            'uuid' => '3fc713ae-f1b8-43a6-95d2-e6d573fab41a',
            'email' => 'tom.smith@email.com',
            'roles' => ['ROLE_USER'],
        ];
        $event = new TokenRefreshAfterGetUserDataEvent(User::class, $userData);

        // When
        $event->setData([
            ...$userData,
            'extraData' => 'something',
        ]);

        // Then
        $this->assertEquals(
            [
                'uuid' => '3fc713ae-f1b8-43a6-95d2-e6d573fab41a',
                'email' => 'tom.smith@email.com',
                'roles' => ['ROLE_USER'],
                'extraData' => 'something',
            ],
            $event->getData(),
        );
    }
}

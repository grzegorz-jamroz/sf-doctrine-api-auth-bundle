<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit\EventSubscriber;

use Ifrost\DoctrineApiAuthBundle\Tests\Unit\BundleTestCase;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\Token;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\EventSubscriber\JWTAuthenticatedEventSubscriberVariant;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Events;

class JWTAuthenticatedEventSubscriberTest extends BundleTestCase
{
    private TokenInterface $authenticationToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventSubscriber = JWTAuthenticatedEventSubscriberVariant::createFromArray([
            'db' => $this->db,
        ]);
        $this->jwtPayload = [
            'uuid' => '25feb06b-0c1e-4416-86fc-706134f2c7de',
            'iat' => 1573457094,
            'exp' => 1573460694,
            'device' => '',
            'roles' => $this->user->getRoles(),
            'username' => $this->user->getEmail(),
        ];
        $this->authenticationToken = $this->createMock(TokenInterface::class);
    }

    public function testShouldGetSubscribedEvents()
    {
        // When & Then
        $this->assertEquals(
            [
                Events::JWT_AUTHENTICATED => 'verifyToken',
            ],
            $this->eventSubscriber->getSubscribedEvents()
        );
    }

    public function testShouldDoNothingWhenJwtExistsInDatabase()
    {
        // Expect
        $this->expectNotToPerformAssertions();
        $this->truncateTable(Token::getTableName());
        $this->db->insert(Token::getTableName(), $this->token->getWritableFormat());

        // Given
        $event = new JWTAuthenticatedEvent($this->jwtPayload, $this->authenticationToken);

        // When & Then
        try {
            $this->eventSubscriber->verifyToken($event);
        } catch (\Exception) {
            $this->assertEquals(1, 1);
        }
    }

    public function testShouldThrowInvalidTokenExceptionWhenJwtDoesNotExistsInDatabase()
    {
        // Expect
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid JWT Token');
        $this->truncateTable(Token::getTableName());

        // Given
        $event = new JWTAuthenticatedEvent($this->jwtPayload, $this->authenticationToken);

        // When & Then
        $this->eventSubscriber->verifyToken($event);
    }
}

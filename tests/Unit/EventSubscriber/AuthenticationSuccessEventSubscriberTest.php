<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit\EventSubscriber;

use Ifrost\DoctrineApiAuthBundle\Generator\RefreshTokenGenerator;
use Ifrost\DoctrineApiAuthBundle\Tests\Unit\BundleTestCase;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\Token;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\User;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\EventSubscriber\AuthenticationSuccessEventSubscriberVariant;
use Ifrost\DoctrineApiBundle\Query\Entity\EntitiesQuery;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Lexik\Bundle\JWTAuthenticationBundle\Signature\CreatedJWS;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationSuccessEventSubscriberTest extends BundleTestCase
{
    private JWSProviderInterface $jwsProvider;
    private JWTManager $jwtManager;
    private RefreshTokenGenerator $refreshTokenGenerator;
    private JWTEncoderInterface $refreshTokenEncoder;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwsProvider = $this->createMock(JWSProviderInterface::class);
        $this->jwtManager = $this->createMock(JWTManager::class);
        $this->refreshTokenGenerator = new RefreshTokenGenerator($this->jwsProvider);
        $this->refreshTokenEncoder = $this->createMock(JWTEncoderInterface::class);
        $this->eventSubscriber = AuthenticationSuccessEventSubscriberVariant::createFromArray([
            'jwtManager' => $this->jwtManager,
            'db' => $this->dbal,
            'refreshTokenGenerator' => $this->refreshTokenGenerator,
            'refreshTokenEncoder' => $this->refreshTokenEncoder,
        ]);
        $this->user = User::createFromArray([
            'uuid' => '3fc713ae-f1b8-43a6-95d2-e6d573fab41a',
            'email' => 'tom.smith@email.com',
            'roles' => ['ROLE_USER'],
        ]);
    }

    public function testShouldGetSubscribedEvents()
    {
        // When & Then
        $this->assertEquals(
            [
                Events::AUTHENTICATION_SUCCESS => 'handle',
            ],
            $this->eventSubscriber->getSubscribedEvents()
        );
    }

    public function testShouldNotEnrichAuthenticationSuccessEventDataWhenUserIsNotInstanceOfEntityInterface()
    {
        // Given
        $data = ['token' => 'jwt_token'];
        $user = $this->createMock(UserInterface::class);
        $event = new AuthenticationSuccessEvent($data, $user, new Response());

        // When
        $this->eventSubscriber->handle($event);

        // Then
        $this->assertEquals(
            $data,
            $event->getData()
        );
    }

    public function testShouldInsertNewTokenDataToDatabase()
    {
        // Expect
        $this->truncateTable(Token::getTableName());
        $this->jwtManager->method('parse')->with('jwt_token')->willReturn([
            'uuid' => '2853c2f5-cb44-46d9-a691-ff2110ff37e5',
            'iat' => 1573449462,
            'exp' => 1573453062,
            'device' => '',
            'roles' => $this->user->getRoles(),
            'username' => $this->user->getEmail(),
        ]);
        $this->jwsProvider->method('create')->willReturn(new CreatedJWS('new_refresh_token', true));
        $this->refreshTokenEncoder->method('decode')->with('new_refresh_token')->willReturn([
            'uuid' => '60efd5f1-d831-4c02-863d-4ee11843fc2e',
            'user_uuid' => $this->user->getUuid(),
            'iat' => 1573457094,
            'exp' => 1573460694,
            'device' => '',
        ]);

        // Given
        $event = new AuthenticationSuccessEvent(
            ['token' => 'jwt_token'],
            $this->user,
            new Response()
        );

        // When
        $this->eventSubscriber->handle($event);
        $tokens = $this->db->fetchAll(EntitiesQuery::class, Token::getTableName());

        // Then
        $this->assertCount(1, $tokens);
        $this->assertEquals(
            [
                'uuid' => '2853c2f5-cb44-46d9-a691-ff2110ff37e5',
                'iat' => 1573449462,
                'exp' => 1573453062,
                'device' => '',
                'user_uuid' => $this->user->getUuid(),
                'refresh_token_uuid' => '60efd5f1-d831-4c02-863d-4ee11843fc2e',
            ],
            $tokens[0],
        );
    }

    public function testShouldEnrichAuthenticationSuccessEventDataWithUser()
    {
        // Expect
        $this->truncateTable(Token::getTableName());
        $this->jwtManager->method('parse')->with('jwt_token')->willReturn([
            'uuid' => '2853c2f5-cb44-46d9-a691-ff2110ff37e5',
            'iat' => 1573449462,
            'exp' => 1573453062,
            'device' => '',
            'roles' => $this->user->getRoles(),
            'username' => $this->user->getEmail(),
        ]);
        $this->jwsProvider->method('create')->willReturn(new CreatedJWS('new_refresh_token', true));
        $this->refreshTokenEncoder->method('decode')->with('new_refresh_token')->willReturn([
            'uuid' => '60efd5f1-d831-4c02-863d-4ee11843fc2e',
            'user_uuid' => $this->user->getUuid(),
            'iat' => 1573457094,
            'exp' => 1573460694,
            'device' => '',
        ]);

        // Given
        $event = new AuthenticationSuccessEvent(
            ['token' => 'jwt_token'],
            $this->user,
            new Response()
        );
        $this->eventSubscriber = AuthenticationSuccessEventSubscriberVariant::createFromArray([
            'returnUserInBody' => true,
            'jwtManager' => $this->jwtManager,
            'db' => $this->dbal,
            'refreshTokenGenerator' => $this->refreshTokenGenerator,
            'refreshTokenEncoder' => $this->refreshTokenEncoder,
        ]);

        // When
        $this->eventSubscriber->handle($event);

        // Then
        $this->assertEquals(
            [
                'token' => 'jwt_token',
                'user' => $this->user,
            ],
            $event->getData()
        );
    }

    public function testShouldEnrichAuthenticationSuccessEventDataWithRefreshTokenInBody()
    {
        // Expect
        $this->truncateTable(Token::getTableName());
        $this->jwtManager->method('parse')->with('jwt_token')->willReturn([
            'uuid' => '2853c2f5-cb44-46d9-a691-ff2110ff37e5',
            'iat' => 1573449462,
            'exp' => 1573453062,
            'device' => '',
            'roles' => $this->user->getRoles(),
            'username' => $this->user->getEmail(),
        ]);
        $this->jwsProvider->method('create')->willReturn(new CreatedJWS('new_refresh_token', true));
        $this->refreshTokenEncoder->method('decode')->with('new_refresh_token')->willReturn([
            'uuid' => '60efd5f1-d831-4c02-863d-4ee11843fc2e',
            'user_uuid' => $this->user->getUuid(),
            'iat' => 1573457094,
            'exp' => 1573460694,
            'device' => '',
        ]);

        // Given
        $event = new AuthenticationSuccessEvent(
            ['token' => 'jwt_token'],
            $this->user,
            new Response()
        );
        $this->eventSubscriber = AuthenticationSuccessEventSubscriberVariant::createFromArray([
            'returnRefreshTokenInBody' => true,
            'jwtManager' => $this->jwtManager,
            'db' => $this->dbal,
            'refreshTokenGenerator' => $this->refreshTokenGenerator,
            'refreshTokenEncoder' => $this->refreshTokenEncoder,
        ]);

        // When
        $this->eventSubscriber->handle($event);

        // Then
        $this->assertEquals(
            [
                'token' => 'jwt_token',
                'refreshToken' => 'new_refresh_token',
            ],
            $event->getData()
        );
    }

    public function testShouldSetCookieWithRefreshToken()
    {
        // Expect
        $this->truncateTable(Token::getTableName());
        $this->jwtManager->method('parse')->with('jwt_token')->willReturn([
            'uuid' => '2853c2f5-cb44-46d9-a691-ff2110ff37e5',
            'iat' => 1573449462,
            'exp' => 1573453062,
            'device' => '',
            'roles' => $this->user->getRoles(),
            'username' => $this->user->getEmail(),
        ]);
        $this->jwsProvider->method('create')->willReturn(new CreatedJWS('new_refresh_token', true));
        $this->refreshTokenEncoder->method('decode')->with('new_refresh_token')->willReturn([
            'uuid' => '60efd5f1-d831-4c02-863d-4ee11843fc2e',
            'user_uuid' => $this->user->getUuid(),
            'iat' => 1573457094,
            'exp' => 1573460694,
            'device' => '',
        ]);

        // Given
        $event = new AuthenticationSuccessEvent(
            ['token' => 'jwt_token'],
            $this->user,
            new Response()
        );
        $this->eventSubscriber = AuthenticationSuccessEventSubscriberVariant::createFromArray([
            'cookieSettings' => [
                'enabled' => true,
                'same_site' => 'lax',
                'path' => '/',
                'domain' => null,
                'http_only' => true,
                'secure' => true,
            ],
            'jwtManager' => $this->jwtManager,
            'db' => $this->dbal,
            'refreshTokenGenerator' => $this->refreshTokenGenerator,
            'refreshTokenEncoder' => $this->refreshTokenEncoder,
        ]);

        // When
        $this->eventSubscriber->handle($event);

        // Then
        $this->assertEquals(
            'new_refresh_token',
            $event->getResponse()->headers->getCookies()[0]->getValue()
        );
    }
}

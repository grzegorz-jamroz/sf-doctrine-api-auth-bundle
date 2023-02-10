<?php

declare(strict_types=1);

namespace Action;

use Ifrost\DoctrineApiAuthBundle\Generator\RefreshTokenGeneratorInterface;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Action\RefreshTokenActionVariant;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\Token;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\User;
use Ifrost\DoctrineApiAuthBundle\TokenExtractor\TokenExtractorInterface as RefreshTokenExtractorInterface;
use Ifrost\DoctrineApiBundle\Exception\NotFoundException;
use Ifrost\DoctrineApiBundle\Query\Entity\EntityQuery;
use Ifrost\DoctrineApiBundle\Utility\DbClient;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Lexik\Bundle\JWTAuthenticationBundle\Signature\LoadedJWS;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RefreshTokenActionTest extends TestCase
{
    public function testShouldReturnResponseWithTokenAndRefreshToken()
    {
        // Given
        $data = $this->getActionData();
        $action = RefreshTokenActionVariant::createFromArray($data);

        // When
        $response = $action->__invoke();

        // Then
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            [
                'token' => 'new_jwt_token',
                'refresh_token' => 'new_refresh_token',
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testShouldReturnResponseAndSetDefaultCookieWhenConfigEnabled()
    {
        // Given
        $data = $this->getActionData();
        $data['cookieSettings']['enabled'] = true;
        $action = RefreshTokenActionVariant::createFromArray($data);

        // When
        $response = $action->__invoke();

        // Then
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            Cookie::create(
                'refresh_token',
                'new_refresh_token',
                $response->headers->getCookies()[0]->getExpiresTime(),
                '/',
                null,
                true,
                true,
                false,
                Cookie::SAMESITE_LAX
            ),
            $response->headers->getCookies()[0]
        );

    }

    public function testShouldThrowInvalidTokenExceptionWhenTokenDoesNotExistInDatabase()
    {
        // Expect
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid JWT Token');

        // Given
        $data = $this->getActionData();
        $db = $this->createMock(DbClient::class);
        $db->method('fetchOne')->with(EntityQuery::class, Token::getTableName(), '25feb06b-0c1e-4416-86fc-706134f2c7de')
            ->willThrowException(new NotFoundException(sprintf('Record not found for query "%s"', EntityQuery::class), 404));
        $data['db'] = $db;
        $action = RefreshTokenActionVariant::createFromArray($data);

        // When & Then
        $action->__invoke();
    }

    public function testShouldThrowJWTDecodeFailureExceptionWhenCurrentTokenIsNotValid()
    {
        // Expect
        $this->expectException(JWTDecodeFailureException::class);
        $this->expectExceptionMessage('Invalid Refresh Token');

        // Given
        $data = $this->getActionData();
        $refreshTokenEncoder = $this->createMock(JWTEncoderInterface::class);
        $refreshTokenEncoder
            ->method('decode')
            ->with('current_refresh_token')
            ->willThrowException(new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'Invalid JWT Token'));
        $data['refreshTokenEncoder'] = $refreshTokenEncoder;
        $action = RefreshTokenActionVariant::createFromArray($data);

        // When & Then
        $action->__invoke();
    }

    public function testShouldThrowJWTDecodeFailureExceptionWhenRefreshTokenIsExpired()
    {
        // Expect
        $this->expectException(JWTDecodeFailureException::class);
        $this->expectExceptionMessage('Expired Refresh Token');

        // Given
        $data = $this->getActionData();
        $refreshTokenEncoder = $this->createMock(JWTEncoderInterface::class);
        $refreshTokenEncoder
            ->method('decode')
            ->with('current_refresh_token')
            ->willThrowException(new JWTDecodeFailureException(JWTDecodeFailureException::EXPIRED_TOKEN, 'Expired JWT Token'));
        $data['refreshTokenEncoder'] = $refreshTokenEncoder;
        $action = RefreshTokenActionVariant::createFromArray($data);

        // When & Then
        $action->__invoke();
    }

    public function testShouldThrowJWTDecodeFailureExceptionWhenRefreshTokenIsUnverified()
    {
        // Expect
        $this->expectException(JWTDecodeFailureException::class);
        $this->expectExceptionMessage('Unable to verify the given Refresh Token through the given configuration. If the "lexik_jwt_authentication.encoder" encryption options have been changed since your last authentication, please renew the token. If the problem persists, verify that the configured keys/passphrase are valid.');

        // Given
        $data = $this->getActionData();
        $refreshTokenEncoder = $this->createMock(JWTEncoderInterface::class);
        $refreshTokenEncoder
            ->method('decode')
            ->with('current_refresh_token')
            ->willThrowException(new JWTDecodeFailureException(JWTDecodeFailureException::UNVERIFIED_TOKEN, 'Unable to verify the given JWT through the given configuration. If the "lexik_jwt_authentication.encoder" encryption options have been changed since your last authentication, please renew the token. If the problem persists, verify that the configured keys/passphrase are valid.'));
        $data['refreshTokenEncoder'] = $refreshTokenEncoder;
        $action = RefreshTokenActionVariant::createFromArray($data);

        // When & Then
        $action->__invoke();
    }

    public function testShouldThrowInvalidTokenExceptionWhenRefreshTokenIsNotValid()
    {
        // Expect
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid Refresh Token');

        // Given
        $data = $this->getActionData();
        $refreshTokenEncoder = $this->createMock(JWTEncoderInterface::class);
        $refreshTokenEncoder
            ->method('decode')
            ->with('current_refresh_token')
            ->willReturn(['token' => 'another_current_refresh_token_db_hash']);
        $data['refreshTokenEncoder'] = $refreshTokenEncoder;
        $action = RefreshTokenActionVariant::createFromArray($data);

        // When & Then
        $action->__invoke();
    }

    public function testShouldThrowInvalidTokenExceptionWhenUserRelatedWithTokenDoesNotExist()
    {
        // Expect
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage(sprintf('Invalid JWT Token - User "%s" does not exist', '166f8006-b4aa-4df3-a9ea-58a3aae2492b'));

        // Given
        $data = $this->getActionData();
        $db = $this->createMock(DbClient::class);
        $db->method('fetchOne')->withConsecutive(
            [EntityQuery::class, Token::getTableName(), '25feb06b-0c1e-4416-86fc-706134f2c7de'],
            [EntityQuery::class, User::getTableName(), '166f8006-b4aa-4df3-a9ea-58a3aae2492b']
        )->willReturnOnConsecutiveCalls(
            [
                'uuid' => '25feb06b-0c1e-4416-86fc-706134f2c7de',
                'user_uuid' => '166f8006-b4aa-4df3-a9ea-58a3aae2492b',
                'iat' => 1673457094,
                'exp' => 1673460694,
                'device' => '',
                'refresh_token' => 'current_refresh_token_db_hash',
            ],
            $this->throwException(new NotFoundException(sprintf('Record not found for query "%s"', EntityQuery::class), 404))
        );

        $data['db'] = $db;
        $action = RefreshTokenActionVariant::createFromArray($data);

        // When & Then
        $action->__invoke();
    }

    private function getActionData(): array
    {
        $request = new Request();
        $oldJwt = 'old_jwt_token';
        $newJwt = 'new_jwt_token';
        $oldJwtUuid = '25feb06b-0c1e-4416-86fc-706134f2c7de';
        $userUuid = '3fc713ae-f1b8-43a6-95d2-e6d573fab41a';
        $currentRefreshToken = 'current_refresh_token';
        $currentRefreshTokenDbHash = 'current_refresh_token_db_hash';
        $newRefreshTokenDbHash = 'new_refresh_token_db_hash';
        $newRefreshToken = 'new_refresh_token';
        $userData = [
            'uuid' => $userUuid,
            'email' => 'tom.smith@email.com',
            'roles' => ['ROLE_USER'],
        ];
        $tokenData = [
            'uuid' => $oldJwtUuid,
            'user_uuid' => $userUuid,
            'iat' => 1673457094,
            'exp' => 1673460694,
            'device' => '',
            'refresh_token' => $currentRefreshTokenDbHash,
        ];
        $request->headers->set('Authorization', sprintf('Bearer %s', $oldJwt));
        $tokenExtractor = $this->createMock(TokenExtractorInterface::class);
        $tokenExtractor->method('extract')->with($request)->willReturn($oldJwt);
        $refreshTokenExtractor = $this->createMock(RefreshTokenExtractorInterface::class);
        $refreshTokenExtractor->method('extract')->willReturn($currentRefreshToken);
        $refreshTokenEncoder = $this->createMock(JWTEncoderInterface::class);
        $refreshTokenEncoder->method('decode')->withConsecutive(
            [$currentRefreshToken],
            [$newRefreshToken],
            [$newRefreshToken],
        )->willReturnOnConsecutiveCalls(
            ['token' => $currentRefreshTokenDbHash],
            ['token' => $newRefreshTokenDbHash],
            ['token' => $newRefreshTokenDbHash],
        );
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $jwsProvider = $this->createMock(JWSProviderInterface::class);
        $jwsProvider->method('load')->with($oldJwt)->willReturn(new LoadedJWS(['uuid' => $oldJwtUuid], true));
        $db = $this->createMock(DbClient::class);
        $db->method('fetchOne')->withConsecutive(
            [EntityQuery::class, Token::getTableName(), $oldJwtUuid],
            [EntityQuery::class, User::getTableName(), $userUuid]
        )->willReturnOnConsecutiveCalls($tokenData, $userData);
        $jwtManager = $this->createMock(JWTManager::class);
        $jwtManager->method('create')->with(User::createFromArray($userData))->willReturn($newJwt);
        $jwtManager->method('parse')->with($newJwt)->willReturn([
            'uuid' => '2853c2f5-cb44-46d9-a691-ff2110ff37e5',
            'iat' => 1673449462,
            'exp' => 1673453062,
            'device' => '',
        ]);
        $refreshTokenGenerator = $this->createMock(RefreshTokenGeneratorInterface::class);
        $refreshTokenGenerator->method('generate')->willReturn($newRefreshToken);

        return [
            'request' => $request,
            'tokenExtractor' => $tokenExtractor,
            'refreshTokenExtractor' => $refreshTokenExtractor,
            'refreshTokenEncoder' => $refreshTokenEncoder,
            'db' => $db,
            'dispatcher' => $dispatcher,
            'jwsProvider' => $jwsProvider,
            'jwtManager' => $jwtManager,
            'refreshTokenGenerator' => $refreshTokenGenerator,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Action;

use Ifrost\DoctrineApiAuthBundle\Generator\RefreshTokenGeneratorInterface;
use Ifrost\DoctrineApiAuthBundle\Payload\JwtPayloadFactory;
use Ifrost\DoctrineApiAuthBundle\Payload\RefreshTokenPayloadFactory;
use Ifrost\DoctrineApiAuthBundle\Query\FindTokenByRefreshTokenUuidQuery;
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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RefreshTokenActionTest extends TestCase
{
    public function testShouldReturnResponseWithTokenInBody()
    {
        // Given
        $data = $this->getActionData();
        $action = RefreshTokenActionVariant::createFromArray($data);

        // When
        $response = $action->__invoke();

        // Then
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            ['token' => 'new_jwt_token'],
            json_decode($response->getContent(), true)
        );
    }

    public function testShouldReturnResponseWithTokenAndRefreshTokenInBody()
    {
        // Given
        $data = $this->getActionData();
        $data['returnRefreshTokenInBody'] = true;
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
        $this->expectExceptionMessage('Invalid Refresh Token');

        // Given
        $data = $this->getActionData();
        $db = $this->createMock(DbClient::class);
        $db->method('fetchOne')->with(FindTokenByRefreshTokenUuidQuery::class, Token::getTableName(), '60efd5f1-d831-4c02-863d-4ee11843fc2e')
            ->willThrowException(new NotFoundException(sprintf('Record not found for query "%s"', EntityQuery::class), 404));
        $data['db'] = $db;
        $action = RefreshTokenActionVariant::createFromArray($data);

        // When & Then
        $action->__invoke();
    }

    public function testShouldThrowJWTDecodeFailureExceptionWhenCurrentRefreshTokenIsNotValid()
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
        $refreshTokenExtractor = $this->createMock(RefreshTokenExtractorInterface::class);
        $refreshTokenExtractor->method('extract')->willReturn('current_refresh_token');
        $data['refreshTokenPayloadFactory'] = new RefreshTokenPayloadFactory(
            $refreshTokenExtractor,
            $refreshTokenEncoder
        );
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
        $refreshTokenExtractor = $this->createMock(RefreshTokenExtractorInterface::class);
        $refreshTokenExtractor->method('extract')->willReturn('current_refresh_token');
        $data['refreshTokenPayloadFactory'] = new RefreshTokenPayloadFactory(
            $refreshTokenExtractor,
            $refreshTokenEncoder
        );
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
        $refreshTokenExtractor = $this->createMock(RefreshTokenExtractorInterface::class);
        $refreshTokenExtractor->method('extract')->willReturn('current_refresh_token');
        $data['refreshTokenPayloadFactory'] = new RefreshTokenPayloadFactory(
            $refreshTokenExtractor,
            $refreshTokenEncoder
        );
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
            ->willReturn(['uuid' => '88f2d11d-6ea4-4f8b-92b0-abb655ab070d']);
        $refreshTokenExtractor = $this->createMock(RefreshTokenExtractorInterface::class);
        $refreshTokenExtractor->method('extract')->willReturn('current_refresh_token');
        $data['refreshTokenPayloadFactory'] = new RefreshTokenPayloadFactory(
            $refreshTokenExtractor,
            $refreshTokenEncoder
        );
        $db = $this->createMock(DbClient::class);
        $db->method('fetchOne')->with(
            FindTokenByRefreshTokenUuidQuery::class,
            Token::getTableName(),
            '88f2d11d-6ea4-4f8b-92b0-abb655ab070d'
        )->willThrowException(
            new NotFoundException(sprintf('Record not found for query "%s"', EntityQuery::class), 404)
        );
        $data['db'] = $db;
        $action = RefreshTokenActionVariant::createFromArray($data);

        // When & Then
        $action->__invoke();
    }

    public function testShouldThrowInvalidTokenExceptionWhenUserRelatedWithTokenDoesNotExist()
    {
        // Expect
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage(sprintf('Invalid JWT Token - User "%s" does not exist', '3fc713ae-f1b8-43a6-95d2-e6d573fab41a'));

        // Given
        $data = $this->getActionData();
        $db = $this->createMock(DbClient::class);
        $db->method('fetchOne')->withConsecutive(
            [FindTokenByRefreshTokenUuidQuery::class, Token::getTableName(), '60efd5f1-d831-4c02-863d-4ee11843fc2e'],
            [EntityQuery::class, User::getTableName(), '3fc713ae-f1b8-43a6-95d2-e6d573fab41a']
        )->willReturnOnConsecutiveCalls(
            $this->getCurrentTokenData(),
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
        $newJwt = 'new_jwt_token';
        $newRefreshTokenUuid = 'new_refresh_token_uuid';
        $newRefreshToken = 'new_refresh_token';

        $userUuid = '3fc713ae-f1b8-43a6-95d2-e6d573fab41a';
        $userData = [
            'uuid' => $userUuid,
            'email' => 'tom.smith@email.com',
            'roles' => ['ROLE_USER'],
        ];

        $currentToken = 'current_token';
        $currentTokenData = $this->getCurrentTokenData();

        $currentRefreshToken = 'current_refresh_token';
        $currentRefreshTokenData = [
            'uuid' => '60efd5f1-d831-4c02-863d-4ee11843fc2e',
            'user_uuid' => $userUuid,
            'iat' => 1773457094,
            'exp' => 1773460694,
            'device' => '',
        ];

        $request->headers->set('Authorization', sprintf('Bearer %s', $currentToken));
        $tokenExtractor = $this->createMock(TokenExtractorInterface::class);
        $tokenExtractor->method('extract')->with($request)->willReturn($currentToken);
        $refreshTokenExtractor = $this->createMock(RefreshTokenExtractorInterface::class);
        $refreshTokenExtractor->method('extract')->willReturn($currentRefreshToken);
        $refreshTokenEncoder = $this->createMock(JWTEncoderInterface::class);
        $refreshTokenEncoder->method('decode')->withConsecutive(
            [$currentRefreshToken],
            [$newRefreshToken],
            [$newRefreshToken],
        )->willReturnOnConsecutiveCalls(
            $currentRefreshTokenData,
            ['uuid' => $newRefreshTokenUuid],
            ['uuid' => $newRefreshTokenUuid],
        );
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $jwsProvider = $this->createMock(JWSProviderInterface::class);
        $jwsProvider->method('load')->with($currentToken)->willReturn(new LoadedJWS($currentTokenData, true));
        $db = $this->createMock(DbClient::class);
        $db->method('fetchOne')->withConsecutive(
            [FindTokenByRefreshTokenUuidQuery::class, Token::getTableName(), $currentTokenData['refresh_token_uuid']],
            [EntityQuery::class, User::getTableName(), $userUuid]
        )->willReturnOnConsecutiveCalls($currentTokenData, $userData);
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
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $jwtPayloadFactory = new JwtPayloadFactory($requestStack, $tokenExtractor, $jwsProvider);
        $refreshTokenPayloadFactory = new RefreshTokenPayloadFactory($refreshTokenExtractor, $refreshTokenEncoder);

        return [
            'jwtPayloadFactory' => $jwtPayloadFactory,
            'refreshTokenPayloadFactory' => $refreshTokenPayloadFactory,
            'refreshTokenEncoder' => $refreshTokenEncoder,
            'db' => $db,
            'dispatcher' => $dispatcher,
            'jwtManager' => $jwtManager,
            'refreshTokenGenerator' => $refreshTokenGenerator,
        ];
    }

    private function getCurrentTokenData(): array
    {
        return [
            'uuid' => '25feb06b-0c1e-4416-86fc-706134f2c7de',
            'user_uuid' => '3fc713ae-f1b8-43a6-95d2-e6d573fab41a',
            'iat' => 1673457094,
            'exp' => 1673460694,
            'device' => '',
            'refresh_token_uuid' => '60efd5f1-d831-4c02-863d-4ee11843fc2e',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Action;

use Ifrost\DoctrineApiAuthBundle\Payload\JwtPayloadFactory;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Action\LogoutActionVariant;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\Token;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\User;
use Ifrost\DoctrineApiBundle\Query\Entity\EntityQuery;
use Ifrost\DoctrineApiBundle\Utility\DbClient;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Signature\LoadedJWS;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LogoutActionTest extends TestCase
{
    public function testShouldReturnEmptyResponse()
    {
        // Given
        $data = $this->getActionData();
        $action = LogoutActionVariant::createFromArray($data);

        // When
        $response = $action->__invoke();

        // Then
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    public function testShouldReturnResponseAndDeleteCookieWhenConfigEnabled()
    {
        // Given
        $data = $this->getActionData();
        $data['cookieSettings']['enabled'] = true;
        $action = LogoutActionVariant::createFromArray($data);

        // When
        $response = $action->__invoke();

        // Then
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            Cookie::create(
                'refresh_token',
                null,
                1,
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

    private function getActionData(): array
    {
        $request = new Request();
        $oldJwt = 'old_jwt_token';
        $oldJwtUuid = '25feb06b-0c1e-4416-86fc-706134f2c7de';
        $userUuid = '3fc713ae-f1b8-43a6-95d2-e6d573fab41a';
        $currentRefreshTokenDbHash = 'current_refresh_token_db_hash';
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
        $db = $this->createMock(DbClient::class);
        $db->method('fetchOne')->withConsecutive(
            [EntityQuery::class, Token::getTableName(), $oldJwtUuid],
            [EntityQuery::class, User::getTableName(), $userUuid]
        )->willReturnOnConsecutiveCalls($tokenData, $userData);
        $db->method('delete')->with(Token::getTableName(), ['uuid' => $oldJwtUuid]);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $jwsProvider = $this->createMock(JWSProviderInterface::class);
        $jwsProvider->method('load')->with($oldJwt)->willReturn(new LoadedJWS(['uuid' => $oldJwtUuid], true, false));
        $jwtPayloadFactory = new JwtPayloadFactory($requestStack, $tokenExtractor, $jwsProvider);

        return [
            'db' => $db,
            'jwtPayloadFactory' => $jwtPayloadFactory,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Action;

use Ifrost\DoctrineApiAuthBundle\Entity\TokenInterface;
use Ifrost\DoctrineApiAuthBundle\Event\TokenRefreshAfterGetUserDataEvent;
use Ifrost\DoctrineApiAuthBundle\Event\TokenRefreshSuccessEvent;
use Ifrost\DoctrineApiAuthBundle\Events;
use Ifrost\DoctrineApiAuthBundle\Generator\RefreshTokenGeneratorInterface;
use Ifrost\DoctrineApiAuthBundle\Payload\JwtPayloadFactory;
use Ifrost\DoctrineApiAuthBundle\Payload\RefreshTokenPayloadFactory;
use Ifrost\DoctrineApiAuthBundle\Query\FindTokenByRefreshTokenUuidQuery;
use Ifrost\DoctrineApiBundle\Exception\NotFoundException;
use Ifrost\DoctrineApiBundle\Query\Entity\EntityQuery;
use Ifrost\DoctrineApiBundle\Utility\DbClient;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PlainDataTransformer\Transform;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RefreshTokenAction
{
    public function __construct(
        readonly private int $ttl,
        readonly private string $tokenParameterName,
        readonly private string $tokenClassName,
        readonly private string $userClassName,
        readonly private bool $validateJwt,
        readonly private bool $returnUserInBody,
        readonly private bool $returnRefreshTokenInBody,
        private array $cookieSettings,
        readonly private JwtPayloadFactory $jwtPayloadFactory,
        readonly private RefreshTokenPayloadFactory $refreshTokenPayloadFactory,
        readonly private JWTEncoderInterface $refreshTokenEncoder,
        readonly private DbClient $db,
        readonly private EventDispatcherInterface $dispatcher,
        readonly private JWTTokenManagerInterface $jwtManager,
        readonly private RefreshTokenGeneratorInterface $refreshTokenGenerator,
    ) {
        $this->cookieSettings = array_merge([
            'enabled' => false,
            'same_site' => 'lax',
            'path' => '/',
            'domain' => null,
            'http_only' => true,
            'secure' => true,
        ], $cookieSettings);
    }

    public function __invoke(): Response
    {
        if ($this->validateJwt === true) {
            $this->jwtPayloadFactory->create();
        }

        $response = new JsonResponse();
        $payload = $this->refreshTokenPayloadFactory->create();

        try {
            $token = $this->getTokenEntity($payload);
        } catch (NotFoundException) {
            throw new InvalidTokenException('Invalid Refresh Token');
        }

        $user = $this->getUser($token->getUserUuid());
        $data = $this->updateToken($token->getUuid(), $user);
        $this->setCookie($data[$this->tokenParameterName], $response);

        if ($this->returnUserInBody === true) {
            $data['user'] = $user;
        }

        if ($this->returnRefreshTokenInBody === false) {
            unset($data[$this->tokenParameterName]);
        }

        $event = new TokenRefreshSuccessEvent($data, $response, $user);
        $this->dispatcher->dispatch($event, Events::TOKEN_REFRESH_SUCCESS);
        $response->setData($event->getData());

        return $response;
    }

    /**
     * @throws NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    private function getTokenEntity(array $payload): TokenInterface
    {
        return $this->tokenClassName::createFromArray(
            $this->db->fetchOne(
                FindTokenByRefreshTokenUuidQuery::class,
                $this->tokenClassName::getTableName(),
                Transform::toString($payload['uuid'] ?? '')
            )
        );
    }

    private function getUser(string $userUuid): UserInterface
    {
        try {
            $userData = $this->db->fetchOne(EntityQuery::class, $this->userClassName::getTableName(), $userUuid);
            $userData['roles'] = json_decode($userData['roles'] ?? [], true);
            $event = new TokenRefreshAfterGetUserDataEvent($this->userClassName, $userData);
            $this->dispatcher->dispatch($event, Events::TOKEN_REFRESH_AFTER_GET_USER_DATA);

            return $this->userClassName::createFromArray($event->getData());
        } catch (\Exception) {
            throw new InvalidTokenException(sprintf('Invalid JWT Token - User "%s" does not exist', $userUuid));
        }
    }

    /**
     * @return array<string, string>
     */
    private function updateToken(
        string $currentTokenUuid,
        UserInterface $user,
    ): array {
        $newToken = $this->jwtManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->generate();
        $payload = $this->refreshTokenEncoder->decode($refreshToken);
        $newTokenEntity = $this->tokenClassName::createFromArray([
            ...$this->jwtManager->parse($newToken),
            'user_uuid' => $user->getUuid(),
            'refresh_token_uuid' => $payload['uuid'],
        ]);
        $this->db->update(
            $this->tokenClassName::getTableName(),
            $newTokenEntity->getWritableFormat(),
            ['uuid' => $currentTokenUuid]
        );

        return [
            'token' => $newToken,
            $this->tokenParameterName => $refreshToken,
        ];
    }

    private function setCookie(string $refreshToken, JsonResponse $response): void
    {
        if ($this->cookieSettings['enabled']) {
            $response->headers->setCookie(
                new Cookie(
                    $this->tokenParameterName,
                    $refreshToken,
                    time() + $this->ttl,
                    $this->cookieSettings['path'],
                    $this->cookieSettings['domain'],
                    $this->cookieSettings['secure'],
                    $this->cookieSettings['http_only'],
                    false,
                    $this->cookieSettings['same_site']
                )
            );
        }
    }
}

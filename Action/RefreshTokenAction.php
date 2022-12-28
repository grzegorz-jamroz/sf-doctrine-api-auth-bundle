<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Action;

use Ifrost\DoctrineApiAuthBundle\Entity\TokenInterface;
use Ifrost\DoctrineApiAuthBundle\Event\TokenRefreshSuccessEvent;
use Ifrost\DoctrineApiAuthBundle\Events;
use Ifrost\DoctrineApiAuthBundle\Generator\RefreshTokenGenerator;
use Ifrost\DoctrineApiBundle\Query\Entity\EntityQuery;
use Ifrost\DoctrineApiBundle\Utility\DbClient;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use PlainDataTransformer\Transform;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RefreshTokenAction
{
    private Request $request;

    public function __construct(
        RequestStack $requestStack,
        private TokenExtractorInterface $tokenExtractor,
        private DbClient $db,
        private EventDispatcherInterface $dispatcher,
        private JWSProviderInterface $jwsProvider,
        private JWTTokenManagerInterface $jwtManager,
        private string $tokenClassName,
        private string $userClassName,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function __invoke(): Response
    {
        $response = new JsonResponse();
        $currentTokenEntity = $this->getCurrentToken();
        $user = $this->getUser($currentTokenEntity->getUserUuid());
        $data = $this->updateToken($currentTokenEntity->getUuid(), $user);
        $response->setData($data);
        $response->headers->setCookie(
            new Cookie(
                'refresh_token',
                $data['refresh_token'],
                time() + 31536000,
                '/',
                null,
                true,
                true,
                false,
                Cookie::SAMESITE_LAX
            )
        );
        $event = new TokenRefreshSuccessEvent($response, $user);
        $this->dispatcher->dispatch($event, Events::TOKEN_REFRESH_SUCCESS);

        return $response;
    }

    private function getCurrentToken(): TokenInterface
    {
        try {
            $token = $this->tokenExtractor->extract($this->request);
            $payload = $this->jwsProvider->load($token)->getPayload();
            $uuid = Transform::toString($payload['uuid'] ?? '');

            return $this->tokenClassName::createFromArray($this->db->fetchOne(EntityQuery::class, $this->tokenClassName::getTableName(), $uuid));
        } catch (\Exception) {
            throw new InvalidTokenException('Invalid JWT Token');
        }
    }

    private function getUser(string $userUuid): UserInterface
    {
        try {
            return $this->userClassName::createFromArray($this->db->fetchOne(EntityQuery::class, $this->userClassName::getTableName(), $userUuid));
        } catch (\Exception) {
            throw new InvalidTokenException('Invalid JWT Token');
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
        $newTokenEntity = $this->tokenClassName::createFromArray([
            ...$this->jwtManager->parse($newToken),
            'user_uuid' => $user->getUuid(),
            'refresh_token' => (new RefreshTokenGenerator())->generate(),
        ]);
        $this->db->update(
            $this->tokenClassName::getTableName(),
            $newTokenEntity->getWritableFormat(),
            ['uuid' => $currentTokenUuid]
        );

        return [
            'token' => $newToken,
            'refresh_token' => $newTokenEntity->getRefreshToken(),
        ];
    }
}

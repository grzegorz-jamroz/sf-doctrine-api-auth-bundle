<h1 align="center">Ifrost Doctrine Api Auth Bundle for Symfony</h1>

<p align="center">
    <strong>Bundle provides authorization for Symfony Doctrine Api bundle with JWT and Refresh Token</strong>
</p>

<p align="center">
    <img src="https://img.shields.io/badge/php->=8.2-blue?colorB=%238892BF" alt="Code Coverage">  
    <img src="https://img.shields.io/badge/coverage-100%25-brightgreen" alt="Code Coverage">   
    <img src="https://img.shields.io/badge/release-v6.4.0-blue" alt="Release Version">   
</p>

## Installation

```
composer require grzegorz-jamroz/sf-doctrine-api-auth-bundle
```

1. Update routing configuration in your project:

```yaml
# config/routes.yaml
controllers:
    resource: ../src/Controller/
    type: attribute

# ...

# add those lines:
ifrost_doctrine_api_controllers:
    resource: ../src/Controller/
    type: doctrine_api_attribute
    
login:
  path: /login

ifrost_doctrine_api_auth:
  resource: Ifrost\DoctrineApiAuthBundle\Routing\DoctrineApiAuthLoader
  type: service
# ...
```

2. Configure Doctrine to store UUIDs as binary strings
```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        types:
            uuid_binary:  Ramsey\Uuid\Doctrine\UuidBinaryType
# Uncomment if using doctrine/orm <2.8
        # mapping_types:
            # uuid_binary: binary
```

**Note:** It is possible to configure Doctrine to store UUIDs in different way - you can read about it [here](https://github.com/ramsey/uuid-doctrine). Please note that bundle will work only with UUIDs stored as binary types.

3. Create User entity which implements [ApiUserInterface](src/Entity/ApiUserInterface.php)

example:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ifrost\DoctrineApiAuthBundle\Entity\ApiUserInterface;
use PlainDataTransformer\Transform;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(readOnly: true)]
class User implements ApiUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid_binary", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private UuidInterface $uuid;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private string $password;

    /**
     * @var array<int, string>
     */
    #[ORM\Column]
    private array $roles;

    public function __construct(
        UuidInterface $uuid,
        string $email,
        string $password = '',
        array $roles = [],
    ) {
        $this->uuid = $uuid;
        $this->email = $email;
        $this->password = $password;
        $this->roles = $roles;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->getEmail();
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public static function getTableName(): string
    {
        return 'user';
    }

    /**
     * @return array<int, string>
     */
    public static function getFields(): array
    {
        return [
            ...array_keys(self::createFromArray([])->jsonSerialize()),
            'password',
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => (string) $this->uuid,
            'email' => $this->email,
            'roles' => $this->getRoles(),
        ];
    }

    public function getWritableFormat(): array
    {
        return [
            ...$this->jsonSerialize(),
            'uuid' => $this->uuid->getBytes(),
            'password' => $this->password,
            'roles' => json_encode($this->getRoles()),
        ];
    }
    
        public static function createFromArray(array $data): static|self
    {
        return new self(
            $data['uuid'] ?? Uuid::uuid7(),
            Transform::toString($data['email'] ?? ''),
            Transform::toString($data['password'] ?? ''),
            Transform::toArray($data['roles'] ?? []),
        );
    }

    public static function createFromRequest(array $data): static|self
    {
        return new self(
            isset($data['uuid']) ? Uuid::fromString($data['uuid']) : Uuid::uuid7(),
            Transform::toString($data['email'] ?? ''),
            Transform::toString($data['password'] ?? ''),
            Transform::toArray($data['roles'] ?? []),
        );
    }
}

```

4. Create Token entity with implements [TokenInterface](src/Entity/TokenInterface.php)

example:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ifrost\DoctrineApiAuthBundle\Entity\TokenInterface;
use PlainDataTransformer\Transform;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(readOnly: true)]
class Token implements TokenInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid_binary", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private string $uuid;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_uuid', referencedColumnName: 'uuid', nullable: false)]
    private string $userUuid;
    
    #[ORM\Column(type: "uuid_binary", unique: true)]
    private string $refreshTokenUuid;

    #[ORM\Column]
    private int $iat;

    #[ORM\Column]
    private int $exp;

    #[ORM\Column(length: 255, nullable: true)]
    private string $device;

    public function __construct(
        UuidInterface $uuid,
        UuidInterface $userUuid,
        UuidInterface $refreshTokenUuid,
        int $iat,
        int $exp,
        string $device,
    ) {
        $this->uuid = $uuid;
        $this->userUuid = $userUuid;
        $this->refreshTokenUuid = $refreshTokenUuid;
        $this->iat = $iat;
        $this->exp = $exp;
        $this->device = $device;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function getUserUuid(): UuidInterface
    {
        return $this->userUuid;
    }
    
    public function getRefreshTokenUuid(): UuidInterface
    {
        return $this->refreshTokenUuid;
    }

    public function getIat(): int
    {
        return $this->iat;
    }

    public function getExp(): int
    {
        return $this->exp;
    }

    public function getDevice(): string
    {
        return $this->device;
    }

    public static function getTableName(): string
    {
        return 'token';
    }

    /**
     * @return array<int, string>
     */
    public static function getFields(): array
    {
        return array_keys(self::createFromArray([])->jsonSerialize());
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => (string) $this->uuid,
            'user_uuid' => (string) $this->userUuid,
            'refresh_token_uuid' => (string) $this->refreshTokenUuid,
            'iat' => $this->iat,
            'exp' => $this->exp,
            'device' => $this->device,
        ];
    }

    public function getWritableFormat(): array
    {
        return [
            ...$this->jsonSerialize(),
            'uuid' => $this->uuid->getBytes(),
            'user_uuid' => $this->userUuid->getBytes(),
            'refresh_token_uuid' => $this->refreshTokenUuid->getBytes(),
        ];
    }
    
    public static function createFromArray(array $data): static|self
    {
        return new self(
            $data['uuid'] ?? Uuid::uuid7(),
            $data['user_uuid'] ?? Uuid::uuid7(),
            $data['refresh_token_uuid'] ?? Uuid::uuid7(),
            Transform::toInt($data['iat'] ?? 0),
            Transform::toInt($data['exp'] ?? 0),
            Transform::toString($data['device'] ?? ''),
        );
    }

    public static function createFromRequest(array $data): static|self
    {
        return new self(
            isset($data['uuid']) ? Uuid::fromString($data['uuid']) : Uuid::uuid7(),
            isset($data['user_uuid']) ? Uuid::fromString($data['user_uuid']) : Uuid::uuid7(),
            isset($data['refresh_token_uuid']) ? Uuid::fromString($data['refresh_token_uuid']) : Uuid::uuid7(),
            Transform::toInt($data['iat'] ?? 0),
            Transform::toInt($data['exp'] ?? 0),
            Transform::toString($data['device'] ?? ''),
        );
    }
}
```

5. Create `config/packages/ifrost_doctrine_api_auth.yaml` file and add:

```yaml
# config/packages/ifrost_doctrine_api_auth.yaml
ifrost_doctrine_api_auth:
  token_entity: 'App\Entity\Token'
  user_entity: 'App\Entity\User'
```

6. Generate the SSL keys [source](https://symfony.com/bundles/LexikJWTAuthenticationBundle/current/index.html#generate-the-ssl-keys)

```
php bin/console lexik:jwt:generate-keypair
```

7. Update security configuration [source](https://symfony.com/bundles/LexikJWTAuthenticationBundle/current/index.html#symfony-5-3-and-higher)

example:

```yaml
# config/packages/security.yaml
security:
  # ...
  enable_authenticator_manager: true
  # https://symfony.com/doc/current/security.html#registering-the-user-hashing-passwords
  password_hashers:
    Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
  # https://symfony.com/doc/current/security.html#loading-the-user-the-user-provider
  providers:
    # used to reload user from session & other features (e.g. switch_user)
    app_user_provider:
      entity:
        class: App\Entity\User
        property: email
  firewalls:
    # ...
    login:
      pattern: ^/login
      stateless: true
      json_login:
        check_path: /login
        success_handler: lexik_jwt_authentication.handler.authentication_success
        failure_handler: lexik_jwt_authentication.handler.authentication_failure
    refresh:
      pattern: ^/token/refresh
      stateless: true
    logout:
      pattern: ^/logout
      stateless: true
    api:
      pattern: ^/
      stateless: true
      jwt: ~
    main:
      lazy: true
      provider: app_user_provider
    # ...
  # Easy way to control access for large sections of your site
  # Note: Only the *first* access control that matches will be used
  access_control:
    - { path: ^/login,          roles: PUBLIC_ACCESS }
    - { path: ^/token/refresh,  roles: PUBLIC_ACCESS }
    - { path: ^/logout,         roles: PUBLIC_ACCESS }
    - { path: ^/,               roles: IS_AUTHENTICATED_FULLY }
  # ...
```

9. Important note for Apache users

Apache server will strip any Authorization header not in a valid HTTP BASIC AUTH format. Read more [here](https://symfony.com/bundles/LexikJWTAuthenticationBundle/current/index.html#important-note-for-apache-users)
To solve this problem add those rules to your VirtualHost configuration:

```
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

10. Configure your Symfony App Databse [source](https://symfony.com/doc/current/doctrine.html)
  - configure the Database in your `.env` file
    ```
    # .env file
    DATABASE_URL="mysql://db_username:password@127.0.0.1:3306/db_name?serverVersion=mariadb-10.6.11&charset=utf8mb4"
    ```
  - create database if not exist yet
    ```
    php bin/console doctrine:database:create
    ```
  - install [symfony/orm-pack](https://github.com/symfony/orm-pack) and [symfony/maker-bundle](https://symfony.com/bundles/SymfonyMakerBundle/current/index.html) if not installed
    ```
    composer require symfony/orm-pack
    composer require symfony/maker-bundle --dev
    ```
  - create migration
    ```
    php bin/console make:migration
    ```
  - run migration
    ```
    php bin/console doctrine:migrations:migrate
    ```

11. Clear cache:

```
php bin/console cache:clear
```

12. Now you can debug your routes. Run command:

```
php bin/console debug:router
```

you should get output:

```
 ------------------- -------- -------- ------ --------------------------
  Name                Method   Scheme   Host   Path
 ------------------- -------- -------- ------ --------------------------
  _preview_error      ANY      ANY      ANY    /_error/{code}.{_format}
  login               ANY      ANY      ANY    /login
  logout              POST     ANY      ANY    /logout
  refresh_token       POST     ANY      ANY    /token/refresh
 ------------------- -------- -------- ------ --------------------------
```

13. Create UserController

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Ifrost\ApiFoundation\Attribute\Api;
use Ifrost\ApiFoundation\Enum\Action;
use Ifrost\DoctrineApiBundle\Controller\DoctrineApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Api(entity: User::class, path: 'users', excludedActions: [Action::CREATE])]
class UserController extends DoctrineApiController
{
    #[Route('/users', name: 'users_create', methods: ['POST'])]
    public function create(): Response
    {
        $data = $this->getApiRequest(User::getFields());
        $data['password'] = $this->getPasswordHasher()->hashPassword(
            User::createFromArray($data),
            $data['password']
        );
        $this->getApiRequestService()->setData($data);

        return $this->getApi()->create();
    }
    
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            UserPasswordHasherInterface::class => '?' . UserPasswordHasherInterface::class,
        ]);
    }
    
    protected function getPasswordHasher(): UserPasswordHasherInterface
    {
        $passwordHasher = $this->container->get(UserPasswordHasherInterface::class);
        $passwordHasher instanceof UserPasswordHasherInterface ?: throw new \RuntimeException(sprintf('Container identifier "%s" is not instance of %s', UserPasswordHasherInterface::class, UserPasswordHasherInterface::class));

        return $passwordHasher;
    }
}
```

14. Now you can debug your routes. Run command:

```
php bin/console debug:router
```

you should get output:

```
 ---------------- -------- -------- ------ --------------------------
  Name             Method   Scheme   Host   Path
 ---------------- -------- -------- ------ --------------------------
  _preview_error   ANY      ANY      ANY    /_error/{code}.{_format}
  users_create     POST     ANY      ANY    /users
  users_find       GET      ANY      ANY    /users
  users_find_one   GET      ANY      ANY    /users/{uuid}
  users_update     PUT      ANY      ANY    /users/{uuid}
  users_modify     PATCH    ANY      ANY    /users/{uuid}
  users_delete     DELETE   ANY      ANY    /users/{uuid}
  login            ANY      ANY      ANY    /login
  logout           POST     ANY      ANY    /logout
  token_refresh    POST     ANY      ANY    /token/refresh
 ---------------- -------- -------- ------ --------------------------
```

15. Temporary set route `users_create` available to the public to make test user:

```yaml
# config/packages/security.yaml
security:
  enable_authenticator_manager: true
  # ...
  firewalls:
    # ...
    user:
      pattern: ^/user
      stateless: true
    # ...
   # ...
  access_control:
    # ...
    - { route: 'users_create',  roles: PUBLIC_ACCESS }
    # ...
  # ...
```

16. Make test user:

```
curl -i -X POST -d '{"email":"test_user@email.com", "password":"top-secret", "roles":["ROLE_ADMIN"]}' http://your-domain.com/users
```

17. Revert `config/packages/security.yaml` to state before point 12:

## Usage

#### Login / Get token
```
curl -i -X POST -d '{"username":"test_user@email.com","password":"top-secret"}' -H "Content-Type: application/json" http://your-domain.com/login
```

example response:

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2NzI4NDgzOTQsImV4cCI6MTY3Mjg1MTk5NCwicm9sZXMiOlsiUk9MRV9BRE1JTiIsIlJPTEVfVVNFUiJdLCJ1c2VybmFtZSI6InRlc3RfdXNlckBlbWFpbC5jb20iLCJ1dWlkIjoiNDVjZjZiOGMtYmJkZi00ZDNlLWI5YWEtN2IyZTY2YTkwM2JmIiwiZGV2aWNlIjpudWxsfQ.FvE0FCRHqwuxDbw-i7mnIu2gYbHAof4mTEnKSWdAy-C9lzpSkMKNCZ01JLGASKYSDur2YJoTujxQZRdtKOyyzwl2hX2_jOstJ0lagdMHXncgAPfaYUurwczAUkjxSeTbikkOLU1afE86RaJl1jr3vB7fJRt1z3JE_enqpwAuFdNhz8JaneoRKG7onEZa6TY-asfSwnVKvTjKSNlE8-54yzgvCKRFZxyhHdI0EuO3mOq_Sx1IOnFdwjx2s3vTLQD1pQl-GMgHy3izyviWu0_VVkifZyh36GEfj2x3Gl0dUOdTXBzqFWgHiPAVFTIAiQU60ETA3WASuU-M3x9R44GqCg",
  "refreshToken": "9ddcc1e382ab8773a0da843b7b5ee3f369b672ff1d46bc5fb0add51de37e054af4024a75689947ed1055689902bc859bf9680740b8a1a954fed1066448a837ea61653866323162612d386435342d343330612d616433362d376539323735376134666363"
}

```

#### Get Refresh Token
```
curl -i -X POST http://grzechu.dshop.com/token/refresh -H "Content-Type: application/json" -H "Authorization: Bearer PLACE_FOR_TOKEN" -d '{"refreshToken":"PLACE_FOR_REFRESH_TOKEN"}' -b XDEBUG_SESSION=PHPSTORM
```

or if config parameter cookie enabled:

```
curl -i -X POST http://grzechu.dshop.com/token/refresh -H "Content-Type: application/json" -H "Authorization: Bearer PLACE_FOR_TOKEN" -b XDEBUG_SESSION=PHPSTORM -b refreshToken=PLACE_FOR_REFRESH_TOKEN
```

example response:

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2NzI4OTkzMDMsImV4cCI6MTY3MjkwMjkwMywicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoidGVzdF91c2VyQGVtYWlsLmNvbSIsInV1aWQiOiI0NDgxOGVlZi1hZjAzLTQ0OTMtOTBjOC01NzkwMzcyNmRhMDIiLCJkZXZpY2UiOm51bGx9.Gswbkzc4pCRkavs7NHPUVgqEFeESWHr7kITbhDP35YOiesTlAKUgcr2U6Dc8s5McNqmyU05bwR3brnTLu9NY1FIhxXA2MwLw-bl75SepHAkKzx9ASdzJ_peXnwbYiHk2p50GyYmTzJzfxY3g921KnJr0SXz6VVl-Xg3kO5ccWR95F3FRzyWZU_JL6Ye8APtHWxGzl6lHxKko9pUb9xdpcgkKPvospLciuH3REz5mdSAs8xxErYeRMWEZl8BBzmAkj0bnVadL3EmliGWnQkG9HgzobE2NePQZH-w5blaZfU3To8AGgwU3O1yIUCCyV8vL1etPltXysx81d0I6gKs9Dw",
  "refreshToken": "r8sYCwAz1MIMKVrScHZ2rmB4Uqul9T_32IMc9MYIEm2BbE2TTzcZ5QmdTixNJWTHbhCySt2Kzj0CTZajtrmMKNgp22i1jYPj.p2lII8MgnTJgYVTDMQGZiRMGU1UDOZiQkd1JVFjgm9MgM9Du1zTO2hzZ4MCqZbOX4eiS2Y5rOXzyMXv-TOUrMN_IbXWNcU7gD3VNzCmZS3cJmVeRZwMcAbPNCBzWDJYko0N1ZizMOSMwh0M2xicjtzDkM2T9i3yF9YMNkKS5Q2NHMMilNEtMTozUOcd9QQGFYYBOcRiLJMA4FO3p5sY0Hh1vYdXY2ygWzexKr27MgC1LgNdMU-0ZOvP2IUskN18CqUYHbVSK2HI9SDTMi2jrKExcxTxRLDZYLsxNwVgczQDPkRZ8FPO4MTHm8kllE5SjMckqGjN0MMM2S1ZF.wz1eJzVsCLzSe22AVODQwXzDjFjMD6UQ3Mu9z0Yv34X11zIZO3H1dMe8zVm2tDDqMmZA87YMszM0UelYliJMJPNR2sDUZN86fk2J3dJF0bZ2mOiA7ykdNOATDv0fXpJ35wYzEYIj_jMVdl-WZM2z8GQzIlRMREIjf3LQEMkagBDizft0OzmugQxM1QM32MITQyRoiEnjzJ4qe9i1BCMyl12ySjkEQqGSD-ghy29T7qGiTZ0l7Ic7FMcKHyFc4HWNiYAgS1j7jjwzMkzMjB"
}
```

## Configuration

You can find default configuration [here](config/packages/ifrost_doctrine_api_auth.yaml). 

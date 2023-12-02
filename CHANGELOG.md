# Changelog
## Unreleased
### Change
- upgrade dependencies
  - upgrade `sf-doctrine-api-bundle` to `v6.2.0`
- changed strategy for storing UUIDs - from string to binary
  - changed [TokenInterface](src/Entity/TokenInterface.php)
  - changed [RefreshTokenAction](src/Action/RefreshTokenAction.php)
  - changed [AuthenticationSuccessEventSubscriber](src/EventSubscriber/AuthenticationSuccessEventSubscriber.php)
  - changed [JWTAuthenticatedEventSubscriber](src/EventSubscriber/JWTAuthenticatedEventSubscriber.php)

### Fix
- fixed all tests due to new requirements from `sf-doctrine-api-bundle` (because of changed strategy for storing UUIDs - from string to binary)
- fixed bundle configuration loading in [IfrostDoctrineApiAuthExtension](src/DependencyInjection/IfrostDoctrineApiAuthExtension.php)

## [v6.2.0] - 2022.03.17
### Add
- First fully tested version


[v6.2.0]: https://github.com/grzegorz-jamroz/sf-doctrine-api-auth-bundle/releases/tag/v6.2.0]

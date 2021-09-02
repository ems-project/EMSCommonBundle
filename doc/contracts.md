# Core API

The common bundle provides a contract for calling an elasticms (core) API.
This codes lives in common because an elasticms backend can call another backend through this api implementation.

## Creating a Core API instance

Create a new service using the [CoreApiFactoryInterface](../src/Contracts/CoreApi/CoreApiFactoryInterface.php) contract.
Your service will be an instance of: [CoreApiInterface](../src/Contracts/CoreApi/CoreApiInterface.php)

```xml
<service id="api_service" class="EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface">
    <factory service="EMS\CommonBundle\Contracts\CoreApi\CoreApiFactoryInterface" method="create"/>
    <argument>%emsch.backend_url%</argument>
</service>
```

## Example

```php
<?php

declare(strict_types=1);

use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiExceptionInterface;

final class Example
{
    private CoreApiInterface $api;

    public function __construct(CoreApiInterface $api)
    {
        $this->api = $api;
    }

    public function testData(string $contentType, array $data): void
    {
        $dataEndpoint = $this->api->data($contentType);

        $draft = $dataEndpoint->create($data);
        try {
            $ouuid = $dataEndpoint->finalize($draft->getRevisionId());
        } catch (CoreApiExceptionInterface $e) {
            $dataEndpoint->discard($draft->getRevisionId());
            throw $e;
        }

        $draftUpdate = $dataEndpoint->update($ouuid, ['test' => 'test']);
        try {
            $dataEndpoint->finalize($draftUpdate->getRevisionId());
        } catch (CoreApiExceptionInterface $e) {
            $dataEndpoint->discard($draftUpdate->getRevisionId());
        }

        $dataEndpoint->delete($ouuid);
    }
}
```

## CoreApi
### Exceptions
> Each API interaction can throw the following **[CoreApiExceptionInterface](../src/Contracts/CoreApi/CoreApiExceptionInterface.php)**:
* **[BaseUrlNotDefinedExceptionInterface](../src/Contracts/CoreApi/Exception/BaseUrlNotDefinedExceptionInterface.php)**
* **[NotAuthenticatedExceptionInterface](../src/Contracts/CoreApi/Exception/NotAuthenticatedExceptionInterface.php)**
* **[NotSuccessfulExceptionInterface](../src/Contracts/CoreApi/Exception/NotSuccessfulExceptionInterface.php)**

### Authentication
* **authenticate**(string $username, string $password): [CoreApiInterface](../src/Contracts/CoreApi/CoreApiInterface.php)
    > Provide EMS login credentials, and it will return an authenticated Core API instance. Throws [NotAuthenticatedExceptionInterface](../src/Contracts/CoreApi/Exception/NotAuthenticatedExceptionInterface.php)
* **isAuthenticated**(): bool
### Endpoints
* **data**(string $contentType): [DataInterface](../src/Contracts/CoreApi/Endpoint/Data/DataInterface.php)
* **user**(): [UserInterface](../src/Contracts/CoreApi/Endpoint/User/UserInterface.php)
### Extra
* **getBaseUrl**(): string
    > Throws [BaseUrlNotDefinedExceptionInterface](../src/Contracts/CoreApi/Exception/BaseUrlNotDefinedExceptionInterface.php)
* **getToken**(): string
    > Before call isAuthenticated, otherwise you will receive an error.
* **setLogger**(LoggerInterface $logger): void
    > Used for overwriting the logger in CLI.
* **test**: void
    > Test if the api available

## Endpoints
### Data ([DataInterface](../src/Contracts/CoreApi/Endpoint/Data/DataInterface.php))
* **create**(array $rawData, ?string $ouuid = null): [DraftInterface](../src/Contracts/CoreApi/Endpoint/Data/DraftInterface.php)
    > When no ouuid is provided elasticms will generate the ouuid. You can receive the generated ouuid by calling finalize. 
* **delete**(string $ouuid): bool
* **discard**(int $revisionId): bool
* **finalize**(int $revisionId): string
    > Return the ouuid if successfully finalized.
* **get**(string $ouuid): [RevisionInterface](../src/Contracts/CoreApi/Endpoint/Data/RevisionInterface.php)
* **replace**(string $ouuid, array $rawData): [DraftInterface](../src/Contracts/CoreApi/Endpoint/Data/DraftInterface.php)
* **update**(string $ouuid, array $rawData): [DraftInterface](../src/Contracts/CoreApi/Endpoint/Data/DraftInterface.php)
    > Will merge the passed rawData with the current rawData.
### User ([UserInterface](../src/Contracts/CoreApi/Endpoint/User/UserInterface.php))
* **getProfiles**(): array
    > Return an array of [ProfileInterface](../src/Contracts/CoreApi/Endpoint/User/ProfileInterface.php) instances
* **getProfileAuthenticated**(): [ProfileInterface](../src/Contracts/CoreApi/Endpoint/User/ProfileInterface.php)






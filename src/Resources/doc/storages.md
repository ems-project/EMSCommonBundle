# Storages

There are many types of file storages supported such as regular file system, asset saved in database, S3 and so forth. It's also possible to extend this list.

To configure the storages of your solution you should  configure the bundle parameter `ems_common.storages` (or via the environment (.env) `EMS_STORAGES` variable if your are using elasticms or the skeleton). This variable must contain a JSON array listing all storage services that you want ordered. I.e.:

```yaml
EMS_STORAGES='[{"type":"fs","path":"./var/assets"},{"type":"fs","path":"./var/assets2"}]'
```

All items must at least contain a `type` attribute. The other attributes depend on the type of service chosen.

## Existing type os storages services

### File system
This service can be instantiated as many as you want and will use a regular folder to save/read assets. 
 - `type` (mandatory): `"fs"`
 - `path` (mandatory): Path where to save assets
 
 Example:
 ```yaml
[
  {
    "type": "fs",
    "path": "/var/lib/ems"
  }
]
```
 
### Entity
This will save/read assets in the default relational database.
 - `type` (mandatory): `"db"`
 
 Example:
 ```yaml
[
  {
    "type": "db"
  }
]
```
 
### HTTP
The will instantiate an HTTP service to read/save assets, typically an elasticms.
 - `type` (mandatory): `"html"`
 - `base-url` (mandatory): The base url (with scheme, protocol, ...) of your service i.e. `http://my-website.eu/admin`
 - `get-url` (optional): the relative url where to get asset by with a file's hash. Default value `/public/file/`
 - `auth-key` (optional): the authentication key to use in order to save asset. If not define the service will be read only.
 
 Example:
 ```yaml
[
  {
    "type": "html",
    "base-url": "http://my-website.eu/admin",
    "auth-key": "MY-AUTH-KEY"
  }
]
```

 ### S3
The will instantiate a S3 client service to read/save assets in a S3 (or a s3-like i.e. minio) bucket. .
 - `type` (mandatory): `"s3"`
 - `credentials` (mandatory): S3 credential object
 - `bucket` (mandatory): Name of the bucket to use
 
 Example:
 ```yaml
[
  {
    "type": "s3",
    "bucket": "mybucket",
    "credentials": {
      "version": "2006-03-01",
      "credentials": {
        "key": "accesskey",
        "secret": "secretkey"
      },
      "region": "us-east-1",
      "endpoint": "http://localhost:9000",
      "use_path_style_endpoint": true
    }
  }
]
```


 ### SFTP
The will instantiate a SFTP client service to read/save assets on a SSH server. See the [PHP ssh2_auth_pubkey_file function documentation](https://www.php.net/manual/en/function.ssh2-auth-pubkey-file.php).
 - `type` (mandatory): `"s3"`.
 - `host` (mandatory): Host name or IP.
 - `path` (mandatory): Path to locate assets.
 - `username` (mandatory): User's login.
 - `public-key-file` (mandatory): Path to a public key. The public key file needs to be in OpenSSH's format. It should look something like: `ssh-rsa AAAAB3NzaC1yc2EAAA....NX6sqSnHA8= rsa-key-20121110`
 - `private-key-file` (mandatory): Path to a private key. 
 - `password-phrase` (optional): If `private-key-file` is encrypted, the passphrase must be provided.
 
 Example:
 ```yaml
[
  {
    "type": "sftp",
    "host": "my-server.local",
    "path": "/var/lib/ems",
    "username": "user",
    "public-key-file": "/home/user/.ssh/id_rsa",
    "private-key-file": "/home/user/.ssh/id_rsa.pub",
    "password-phrase": "my-secret-key"
  }
]
```

## Extend with a specific storage type
If you want to implement your own storage service here is a quick tutorial.

Register a Symfony service implementing the `EMS\CommonBundle\Storage\Factory\StorageFactoryInterface` interface. The service must be tagged with `ems_common.storage.factory`:
```xml
    <service id="app.storage.ftp_factory" class="App\Storage\FtpFactory">
        <argument type="service" id="logger" />
        <tag name="`ems_common.storage.factory`" alias="ftp"/>
    </service>
```

This interface has 2 function to implement:
 - `public function createService(array $parameters): ?StorageInterface;` : This should return an instance of a class implementing the `EMS\CommonBundle\Storage\Service\StorageInterface`
 - `public function getStorageType(): string;`: this function should just return the string that you want to register as type for your storage service. I.e. `ftp`.

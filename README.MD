# DoctrineAuditBundle

Inspired by

This bundle creates audit logs for all Doctrine ORM database related changes:

- inserts and updates including their diffs and relation field diffs.
- many to many relation changes, association and dissociation actions.
- if there is an user in token storage, it is used to identify the user who made the changes.
- the audit entries are inserted within the same transaction during **flush**, if something fails the state remains clean.

Basically you can track any change from these log entries if they were
managed through standard **ORM** operations.

**NOTE:** audit cannot track DQL or direct SQL updates or delete statement executions.

This bundle is inspired by [damienharper/doctrine-audit-bundle](https://github.com/DamienHarper/DoctrineAuditBundle) and
[simplethings/entity-audit-bundle](https://github.com/simplethings/EntityAuditBundle.git)



Installation
============

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```bash
composer require kricha/doctrine-audit-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
composer require kricha/doctrine-audit-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Kricha\DoctrineAuditBundle\KrichaDoctrineAuditBundle(),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(), // only required if you plan to use included viewer/templates
        );

        // ...
    }

    // ...
}
```


Configuration
=============

### Audited entities and properties

By default, DoctrineAuditBundle won't audit any entity, you have to configure which entities have to be audited.

```yaml
// app/config/config.yml (symfony < 3.4)
// config/packages/kricha_doctrine_audit.yaml (symfony >= 3.4)
kricha_doctrine_audit:
    entities:
        App\Entity\Order: ~
        App\Entity\User: ~
```

All `Order` and `User` properties will be audited.
Though it is possible to exclude some of them from the audit process.

```yaml
// app/config/config.yml (symfony < 3.4)
// config/packages/kricha_doctrine_audit.yaml (symfony >= 3.4)
kricha_doctrine_audit:
    entities:
        App\Entity\Order: ~   # all MyAuditedEntity1 properties are audited
        App\Entity\User:
            ignored_columns:                  # properties ignored by the audit process
                - createdAt
                - updatedAt
```

It is also possible to specify properties that are globally ignored by the audit process.

```yaml
// app/config/config.yml (symfony < 3.4)
// config/packages/kricha_doctrine_audit.yaml (symfony >= 3.4)
kricha_doctrine_audit:
    ignored_columns:    # properties ignored by the audit process in any audited entity
        - createdAt
        - updatedAt
```

### Audit tables naming format

Audit table names are composed of a prefix, the audited table name and a suffix.
By default, the prefix is empty and the suffix is `_audit`. Though, they can be customized.

```yaml
// app/config/config.yml (symfony < 3.4)
// config/packages/kricha_doctrine_audit.yaml (symfony >= 3.4)
kricha_doctrine_audit:
    table_prefix: ''
    table_suffix: '_audit'
```

### Creating audit tables

Open a command console, enter your project directory and execute the
following command to review the new audit tables in the update schema queue.

```bash
# symfony < 3.4
app/console doctrine:schema:update --dump-sql
```

```bash
# symfony >= 3.4
bin/console doctrine:schema:update --dump-sql
```

**Notice**: DoctrineAuditBundle currently **only** works with a DBAL Connection and EntityManager named **"default"**.


#### Using [Doctrine Migrations Bundle](http://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html)

```bash
# symfony < 3.4
app/console doctrine:migrations:diff
app/console doctrine:migrations:migrate
```

```bash
# symfony >= 3.4
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

#### Using Doctrine Schema

```bash
# symfony < 3.4
app/console doctrine:schema:update --force
```

```bash
# symfony >= 3.4
bin/console doctrine:schema:update --force
```

### Audit viewer

Add the following routes to the routing configuration to enable the included audits viewer.

```yaml
// app/config/routing.yml (symfony < 3.4)
// config/routes.yaml (symfony >= 3.4)
kricha_doctrine_audit:
    resource: "@KrichaDoctrineAuditBundle/Controller/"
    type: annotation
```

It is possible to filter results by event type by calling `AuditReader::filterBy` method before getting the results.

Available constants are:
````php
    AuditManager::INSERT
    AuditManager::UPDATE
    AuditManager::DELETE
    AuditManager::ASSOCIATE
    AuditManager::DISSOCIATE
````

### Custom user provider

If you don't use Symfony's `TokenStorage` to save your current user, you can configure a custom username for changes.

````php
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Kricha\DoctrineAuditBundle\AuditConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(EntityManagerInterface $entityManager, AuditConfiguration $auditConfiguration)
    {
        $auditConfiguration->setUsernameCallable(
            static function () {
                return '[ControllerChanger]';
            }
        );
        $order      = $this->entityManager->find(Order::class, 1);
        $order->setComment(\uniqid());
        $entityManager->persist($order);
        $entityManager->flush();

        return $this->render('index.html.twig', ['hello' => 'wolrd']);
    }
}
````

Usage
=====

**audit** entities will be mapped automatically if you run schema update or similar.
And all the database changes will be reflected in the audit logs afterwards.



FAQ:
====

#### I've added an new entity in the config file but it's not audited.

> First check its namespace, then clear your cache and re-run `doctrine:schema:update` or `doctrine:migrations:migrate`.

Contributing
============

DoctrineAuditBundle is an open source project. Contributions made by the community are welcome. Send us your ideas, code reviews, pull requests and feature requests to help us improve this project.

Do not forget to provide unit tests when contributing to this project.


License
=======

DoctrineAuditBundle is free to use and is licensed under the [MIT license](http://www.opensource.org/licenses/mit-license.php)

# Oso PHP

[![Test](https://github.com/J0sh0nat0r/php-oso/actions/workflows/test.yml/badge.svg)](https://github.com/J0sh0nat0r/php-oso/actions/workflows/test.yml)
[![codecov](https://codecov.io/gh/J0sh0nat0r/php-oso/branch/master/graph/badge.svg?token=UuG4plut6l)](https://codecov.io/gh/J0sh0nat0r/php-oso)
[![StyleCI](https://github.styleci.io/repos/483424130/shield)](https://github.styleci.io/repos/483424130)


This library integrates the authorization framework [Oso](https://github.com/osohq/oso) with PHP, since it currently
lacks an official integration.

## Installation:
`composer require j0sh0nat0r/oso-php` (NOTE: The package is not yet available so installation from git is required)

## Quick example:
```PHP
class Repository {
    public function __construct(public string $name, public bool $isPublic) { }
}

class Role {
    public function __construct(public string $name, public Repository $repository) { }
}

class User {
    public function __construct(public array $roles) { }
}

$reposDb = [
    'gmail' => new Repository('gmail', false),
    'react' => new Repository('react', true),
    'oso' => new Repository('oso', false)
];

$usersDb = [
    'larry' => new User([new Role('admin', $reposDb['gmail'])]),
    'anne' => new User([new Role('maintainer', $reposDb['react'])]),
    'graham' => new User([new Role('contributor', $reposDb['oso'])]),
];

$oso = new \J0sh0nat0r\Oso\Oso();

$oso->registerClass(User::class);
$oso->registerClass(Repository::class);

$oso->loadStr(<<<POLAR
actor User {}

resource Repository {
  permissions = ["read", "push", "delete"];
  roles = ["contributor", "maintainer", "admin"];

  "read" if "contributor";
  "push" if "maintainer";
  "delete" if "admin";

  "maintainer" if "admin";
  "contributor" if "maintainer";
}

# This rule tells Oso how to fetch roles for a repository
has_role(actor: User, role_name: String, repository: Repository) if
  role in actor.roles and
  role_name = role.name and
  repository = role.repository;

has_permission(_actor: User, "read", repository: Repository) if
  repository.isPublic;

allow(actor, action, resource) if
  has_permission(actor, action, resource);
POLAR
);

// Check a single permission
echo 'Larry can push to gmail: ' . var_export($oso->isAllowed($usersDb['larry'], 'push', $reposDb['gmail']), true) . PHP_EOL;

// Using queries as iterators
// Note that in practice `authorizedActions` would be used here
foreach ($reposDb as $repo) {
    echo "$repo->name:", PHP_EOL;

    foreach ($usersDb as $name => $user) {
        // Here we ask Oso to return the allowed action to us using a variable
        $query = $oso->queryRule('allow', [], false, $user, new \J0sh0nat0r\Oso\Variable('action'), $repo);

        $allowedActions = array_column(iterator_to_array($query), 'action');

        if (!empty($allowedActions)) {
            echo "\t$name: ", implode(', ', $allowedActions), PHP_EOL;
        }
    }
}
```

# Kubernetes DNS/Service locator for PHP
[![Latest Stable Version](https://poser.pugx.org/patrickkusebauch/kubernetes-dns/v)](https://www.packagist.org/packages/patrickkusebauch/kubernetes-dns)
[![License](https://poser.pugx.org/patrickkusebauch/kubernetes-dns/license)](https://vwww.packagist.org/packages/patrickkusebauch/kubernetes-dns)

A simple library that will translate the Kubernetes service name into a host address either from ENV variables or from the K8s running DNS service 

## Installation
The best way to install the library is via `composer`:
```
composer require patrickkusebauch/kubernetes-dns
```

## Usage
Simplest form(default config):
```php
use PatrickKusebauch\KubernetesDNS\ServiceLocator;

$locator = new ServiceLocator();
$locator->serviceHost('name-of-my-service'); //name-of-my-service
```

Advanced:
```php
use PatrickKusebauch\KubernetesDNS\ServiceLocator;

$locator = new ServiceLocator([
    'preferredSource' => null, 
    'allowedSources' => [ServiceLocator::SOURCE_ENV]]
);

$locator->serviceHost('name-of-my-service', ServiceLocator::SOURCE_ENV); //virtual IP address

$locator->changeAllowedSources([ServiceLocator::SOURCE_DNS]);
$locator->changePreferredSource(ServiceLocator::SOURCE_DNS);

$locator->serviceHost('name-of-my-service'); //name-of-my-service
```

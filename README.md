# Majora Generator Bundle

Bundle which provide a simple way to generate an empty code structure from skeleton files using generic tags.



## Installation

The recommended installation method is through composer:

```bash
composer require --dev majora/generator-bundle
```



## Configuration

The Majora Generator needs to have skeletons installed somewhere before generating stuff in your project (take a look at [our base skeletons](#majora-base-skeletons) if you don't know where to start).

When you're done with your project skeleton files, you'll have to tell the generator where to find them :

```yml
# config_dev.yml

majora_generator:
    skeletons_path: %kernel.root_dir%/../skeletons/symfony-standard
```



## Usage

```bash
php app/console majora:generate Vendor Namespace Entity
```



## Majora Base Skeletons

Majora Generator Bundle also provides some base skeletons which are waiting to be installed, customized and heavily generated in your projects.


### Available base skeletons

| Skeleton name | Description |
|:-------------:|-------------|
| symfony-standard | [README](src/Majora/Bundle/GeneratorBundle/Resources/skeletons/symfony-standard) |


### Install a base skeleton

```bash
php app/console majora:generate:skeleton symfony-standard
```

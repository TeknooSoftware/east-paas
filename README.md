Teknoo Software - PaaS library
=================================

[![Build Status](https://travis-ci.com/TeknooSoftware/east-paas.svg?branch=master)](https://travis-ci.com/TeknooSoftware/east-paas)
[![Latest Stable Version](https://poser.pugx.org/teknoo/east-paas/v/stable)](https://packagist.org/packages/teknoo/east-paas)
[![Latest Unstable Version](https://poser.pugx.org/teknoo/east-paas/v/unstable)](https://packagist.org/packages/teknoo/east-paas)
[![Total Downloads](https://poser.pugx.org/teknoo/east-paas/downloads)](https://packagist.org/packages/teknoo/east-paas)
[![License](https://poser.pugx.org/teknoo/east-paas/license)](https://packagist.org/packages/teknoo/east-paas)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

Universal package, following the #East programming philosophy, build on Teknoo/East-Foundation (and Teknoo/Recipe),
and implementing a basic CMS to display dynamics pages with different types and templates.

Example with Symfony
--------------------

   

Support this project
---------------------

This project is free and will remain free, but it is developed on my personal time. 
If you like it and help me maintain it and evolve it, don't hesitate to support me on [Patreon](https://patreon.com/teknoo_software).
Thanks :) Richard. 

Installation & Requirements
---------------------------
To install this library with composer, run this command :

    composer require teknoo/east-paas

This library requires :

    * PHP 7.4+
    * A PHP autoloader (Composer is recommended)
    * Teknoo/Immutable.
    * Teknoo/States.
    * Teknoo/Recipe.
    * Teknoo/East-Foundation.
    * Optional: Symfony 4.4+ (for administration)

News from Teknoo Website 2.0
----------------------------

This library requires PHP 7.4 or newer and it's only compatible with Symfony 4.4 or newer, Some change causes bc breaks :
- PHP 7.4 is the minimum required
- Replace array_merge by "..." operators
- Remove some PHP useless DockBlocks
- Switch to typed properties
- Most methods have been updated to include type hints where applicable. Please check your extension points to make sure the function signatures are correct.
_ All files use strict typing. Please make sure to not rely on type coercion.
- Set default values for Objects.  
- Set dependencies defined into PHP-DI used in Symfony as synthetic
  services into Symfony's services definitions to avoid compilation error with Symfony 4.4
- Enable PHPStan in QA Tools and disable PHPMd
- Enable PHPStan extension dedicated to support Stated classes

Credits
-------
Richard Déloge - <richarddeloge@gmail.com> - Lead developer.
Teknoo Software - <https://teknoo.software>

About Teknoo Software
---------------------
**Teknoo Software** is a PHP software editor, founded by Richard Déloge.
Teknoo Software's goals : Provide to our partners and to the community a set of high quality services or software,
 sharing knowledge and skills.

License
-------
East Website is licensed under the MIT License - see the licenses folder for details

Contribute :)
-------------

You are welcome to contribute to this project. [Fork it on Github](CONTRIBUTING.md)

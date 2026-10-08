<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\Config\TesterOptions;
use Behat\PHPUnitAssertionsExtension\BehatPHPUnitAssertionsExtension;
use Behat\PHPUnitAssertionsExtension\PHPUnitExceptionStringer;
use DMarynicz\BehatParallelExtension\Extension as BehatParallelExtension;
use Teknoo\Tests\East\Paas\Behat\FeatureContext;

// behat/phpunit-assertions-extension 1.0.0 imports a class removed in Behat 4.0 : to remove when fixed upstream
class_alias(PHPUnitExceptionStringer::class, 'Behat\Testwork\Exception\Stringer\PHPUnitExceptionStringer');

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withTesterOptions(
                (new TesterOptions())
                    ->withStopOnFailure(false)
                    ->withStrictResultInterpretation(true)
            )
            ->withExtension(new Extension(BehatParallelExtension::class))
            ->withExtension(new Extension(BehatPHPUnitAssertionsExtension::class))
            ->withSuite(
                (new Suite('default'))
                    ->addContext(FeatureContext::class, ['timeoutSeconds' => 25])
            )
    );

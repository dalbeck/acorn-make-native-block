<?php

return [
    'providers' => [
        \YourNamespace\YourPackage\Providers\BlockServiceProvider::class,
    ],
    'commands' => [
        \YourNamespace\YourPackage\Console\MakeNativeBlockCommand::class,
    ],
];

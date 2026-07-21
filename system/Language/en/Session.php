<?php

declare(strict_types=1);




return [
    'missingDatabaseTable'   => 'Session: "savePath" must have the table name for the Database Session Handler to work.',
    'invalidSavePath'        => 'Session: Configured save path "{0}" is not a directory, does not exist or cannot be created.',
    'writeProtectedSavePath' => 'Session: Configured save path "{0}" is not writable by the PHP process.',
    'emptySavePath'          => 'Session: No save path configured.',
    'invalidSavePathFormat'  => 'Session: Invalid Redis save path format: "{0}"',

    
    'invalidSameSiteSetting' => 'Session: The SameSite setting must be None, Lax, Strict, or a blank string. Given: "{0}"',
];

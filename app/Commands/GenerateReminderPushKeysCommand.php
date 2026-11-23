<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Creates the VAPID key pair browser push needs. Run once per environment (staging and production each get their own),
 * then add the three lines it prints to that environment's .env. Never commit the private key.
 *
 *   php spark attendance:push-keys
 */
final class GenerateReminderPushKeysCommand extends BaseCommand
{
    protected $group       = 'AIS';
    protected $name        = 'attendance:push-keys';
    protected $description = 'Generates the VAPID keys for attendance-reminder browser push.';
    protected $usage       = 'attendance:push-keys';

    public function run(array $params)
    {
        if (! class_exists(\Minishlink\WebPush\VAPID::class)) {
            CLI::error('The push library is not installed. Run: composer install');
            return EXIT_ERROR;
        }

        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
        CLI::write('Add these lines to this environment\'s .env (keep the private key secret):');
        CLI::newLine();
        CLI::write('push.vapidPublic  = ' . $keys['publicKey']);
        CLI::write('push.vapidPrivate = ' . $keys['privateKey']);
        CLI::write('push.subject      = mailto:admin@your-company.example');
        CLI::newLine();
        CLI::write('Change the push.subject address to a real contact. Existing devices must re-enable push after the keys change.', 'yellow');
        return EXIT_SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\MailTemplateService;
use Illuminate\Console\Command;

class SeedMailTemplates extends Command
{
    protected $signature = 'mail:seed-templates';
    protected $description = 'Seed default mail templates into Firestore';

    public function handle(): int
    {
        $types = array_keys(config('constant.mail_template_setting', []));
        if (count($types) === 0) {
            $this->warn('No template types configured.');
            return Command::SUCCESS;
        }

        $count = app(MailTemplateService::class)->seedDefaults($types);
        $this->info('Seeded ' . $count . ' templates.');
        return Command::SUCCESS;
    }
}

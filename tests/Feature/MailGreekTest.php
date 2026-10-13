<?php

namespace Tests\Feature;

use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class MailGreekTest extends TestCase
{
    public function test_greek_mail_strings_and_footer(): void
    {
        app()->setLocale('el');
        config(['app.name' => '2bit']);

        $html = (string) (new MailMessage)
            ->line('Δοκιμή')
            ->action('Αποδοχή πρόσκλησης', 'https://example.test/invites/abc')
            ->render();

        $this->assertStringContainsString('Γεια σας!', $html);
        $this->assertStringContainsString('Με εκτίμηση,', $html);
        $this->assertStringContainsString('κουμπί «Αποδοχή πρόσκλησης»', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
        $this->assertStringNotContainsString('Hello!', $html);
    }

    public function test_reminder_wording_is_singular_for_one_day(): void
    {
        app()->setLocale('el');

        $this->assertSame('2bit: το example.gr λήγει σε 1 ημέρα', trans_choice('app.mail.expiring_subject', 1, ['name' => 'example.gr', 'days' => 1]));
        $this->assertSame('2bit: το example.gr λήγει σε 7 ημέρες', trans_choice('app.mail.expiring_subject', 7, ['name' => 'example.gr', 'days' => 7]));
        $this->assertSame('Αυτή είναι υπενθύμιση 1 ημέρα πριν την προθεσμία.', trans_choice('app.mail.expiring_before', 1, ['days' => 1]));

        app()->setLocale('en');
        $this->assertSame('Duvento: x expires in 7 days', trans_choice('app.mail.expiring_subject', 7, ['name' => 'x', 'days' => 7]));
    }
}

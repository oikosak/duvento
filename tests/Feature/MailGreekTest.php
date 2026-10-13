<?php

namespace Tests\Feature;

use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class MailGreekTest extends TestCase
{
    public function test_greek_mail_strings_and_footer(): void
    {
        app()->setLocale('el');
        config(['app.name' => '2bit Ανανεώσεις']);

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
}

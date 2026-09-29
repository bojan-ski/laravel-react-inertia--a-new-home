<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class SavedSearchDigestNotification extends MailOnlyNotification
{
    /**
     * @param  list<array{name: string, url: string, listings: list<array{title: string, url: string, price: string, city: string}>, total: int}>  $sections
     */
    public function __construct(
        public array $sections,
        public string $manageUrl,
        public string $unsubscribeUrl,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('app.emails.saved_search_digest.subject'))
            ->greeting(__('app.emails.saved_search_digest.greeting'))
            ->line(__('app.emails.saved_search_digest.intro'));

        foreach ($this->sections as $section) {
            $mail->line('**'.$this->escape($section['name']).'**');

            foreach ($section['listings'] as $listing) {
                $mail->line(__('app.emails.saved_search_digest.listing', [
                    'title' => '['.$this->escape($listing['title']).']('.$listing['url'].')',
                    'price' => $listing['price'],
                    'city' => $this->escape($listing['city']),
                ]));
            }

            $remaining = $section['total'] - count($section['listings']);

            if ($remaining > 0) {
                $mail->line(trans_choice('app.emails.saved_search_digest.more', $remaining, ['count' => $remaining]));
            }

            $mail->line('['.__('app.emails.saved_search_digest.view_all').']('.$section['url'].')');
        }

        return $mail
            ->action(__('app.emails.saved_search_digest.action'), $this->manageUrl)
            ->line(__('app.emails.saved_search_digest.outro'))
            ->line('['.__('app.emails.saved_search_digest.unsubscribe').']('.$this->unsubscribeUrl.')');
    }

    private function escape(string $text): string
    {
        return preg_replace('/([\\\\`*_\[\]])/', '\\\\$1', $text);
    }
}

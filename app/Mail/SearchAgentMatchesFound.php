<?php

namespace App\Mail;

use App\Models\SearchAgent;
use App\Models\SearchAgentListingMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SearchAgentMatchesFound extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Marks visits to the results page that came from this email, so they count as email clicks.
     */
    public const LINK_REFERRER = 'email';

    public function __construct(
        public SearchAgent $searchAgent,
        public SearchAgentListingMatch $match,
    ) {}

    public function envelope(): Envelope
    {
        $title = $this->searchAgent->title !== '' ? $this->searchAgent->title : 'Suchagent';

        return new Envelope(
            subject: sprintf('Immotickr: %d neue Treffer für "%s"', $this->match->match_count, $title),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.search-agent-matches-found',
            with: [
                'searchAgent' => $this->searchAgent,
                'matchCount' => $this->match->match_count,
                'resultsUrl' => route('search-agent-results.show', [
                    'token' => $this->match->public_token,
                    'ref' => self::LINK_REFERRER,
                ]),
            ],
        );
    }
}

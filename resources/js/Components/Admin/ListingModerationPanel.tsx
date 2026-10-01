import ListingModerationActions from '@/Components/Admin/ListingModerationActions'
import { formatDate } from '@/Components/Listings/listingFormat'
import Kicker from '@/Components/UI/Kicker'
import StatusBadge from '@/Components/UI/StatusBadge'
import type { ListingModerationPanelProps } from '@/types/admin'

const rowClasses = 'flex justify-between gap-4 border-b border-rule py-3 text-sm'

export default function ListingModerationPanel({
    listing,
    canModerate,
    translations,
    className = 'flex',
}: ListingModerationPanelProps) {
    const review = translations.review

    return (
        <section className={`flex-col gap-5 ${className}`}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <Kicker as="h2">{review.moderation_heading}</Kicker>
                <StatusBadge status={listing.status} label={review.statuses[listing.status]} />
            </div>

            <dl className="flex flex-col border-t border-rule">
                <div className={rowClasses}>
                    <dt className="shrink-0 text-ink-muted">{review.owner_label}</dt>
                    <dd className="flex min-w-0 flex-col items-end text-right">
                        <span className="wrap-break-word">{listing.ownerName ?? ''}</span>
                        <span className="text-[13px] text-ink-muted">{review.owner_types[listing.ownerType]}</span>
                        {listing.ownerEmail && (
                            <a
                                href={`mailto:${listing.ownerEmail}`}
                                className="text-[13px] break-all text-ink-muted transition-colors duration-150 hover:text-forest"
                            >
                                {listing.ownerEmail}
                            </a>
                        )}
                    </dd>
                </div>
                <div className={rowClasses}>
                    <dt className="text-ink-muted">{review.submitted_label}</dt>
                    <dd className="text-right">{formatDate(listing.createdAt, 'long')}</dd>
                </div>
                {listing.expiresAt && (
                    <div className={rowClasses}>
                        <dt className="text-ink-muted">{review.expires_label}</dt>
                        <dd className="text-right">{formatDate(listing.expiresAt, 'long')}</dd>
                    </div>
                )}
            </dl>

            {listing.rejectionReason && (
                <div className="flex flex-col gap-1 bg-sand px-3 py-2.5 text-[13px]">
                    <p className="font-medium">{review.rejection_reason_label}</p>
                    <p className="whitespace-pre-line wrap-break-word">{listing.rejectionReason}</p>
                </div>
            )}

            {canModerate && (
                <ListingModerationActions
                    listingId={listing.id}
                    listingTitle={listing.title}
                    translations={translations}
                    layout="stacked"
                />
            )}
        </section>
    )
}

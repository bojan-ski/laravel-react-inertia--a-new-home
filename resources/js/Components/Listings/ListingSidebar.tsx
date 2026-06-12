import { router } from '@inertiajs/react'
import { Heart, Mail, MessageCircle, Phone, User } from 'lucide-react'
import { useState } from 'react'
import { formatPrice } from '@/Components/Listings/listingFormat'
import type { ListingSidebarProps } from '@/types/listing'

export default function ListingSidebar({ listing, isBookmarked, canStartChat, translations }: ListingSidebarProps) {
    const isRental = listing.listingType === 'rent'
    const hasContact = Boolean(listing.contactName || listing.contactEmail || listing.contactPhone)
    const [isToggling, setIsToggling] = useState(false)

    function toggleBookmark() {
        router.post(
            `/listings/${listing.id}/bookmark`,
            {},
            {
                preserveScroll: true,
                onStart: () => setIsToggling(true),
                onFinish: () => setIsToggling(false),
            }
        )
    }

    return (
        <aside className="lg:sticky lg:top-8">
            <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm">
                <div className="flex items-baseline gap-1">
                    <span className="font-display text-3xl font-semibold text-text">
                        {formatPrice(listing.price, listing.currency)}
                    </span>
                    {isRental && <span className="text-sm text-text-muted">{translations.per_month}</span>}
                </div>

                <div className="mt-6 flex flex-col gap-3 border-t border-border pt-6">
                    <button
                        type="button"
                        onClick={toggleBookmark}
                        disabled={isToggling}
                        className={`flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-60 ${
                            isBookmarked
                                ? 'bg-brand-soft text-brand'
                                : 'border border-border bg-surface text-text hover:bg-surface-muted'
                        }`}
                    >
                        <Heart className={`size-4 ${isBookmarked ? 'fill-current' : ''}`} />
                        {isBookmarked ? translations.saved : translations.save_listing}
                    </button>

                    <button
                        type="button"
                        disabled={!canStartChat}
                        className="flex w-full items-center justify-center gap-2 rounded-lg border border-border bg-surface px-4 py-3 text-sm font-medium text-text transition-colors hover:bg-surface-muted disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-surface"
                    >
                        <MessageCircle className="size-4" />
                        {translations.start_chat}
                    </button>
                </div>

                {hasContact && (
                    <div className="mt-6 border-t border-border pt-6">
                        <h2 className="text-xs font-medium uppercase tracking-wide text-text-muted">
                            {translations.contact_heading}
                        </h2>

                        <div className="mt-4 flex flex-col gap-3 text-sm">
                            {listing.contactName && (
                                <div className="flex items-center gap-3 text-text">
                                    <User className="size-4 shrink-0 text-text-muted" />
                                    <span>{listing.contactName}</span>
                                </div>
                            )}

                            {listing.contactEmail && (
                                <a
                                    href={`mailto:${listing.contactEmail}`}
                                    className="flex items-center gap-3 text-text transition-colors hover:text-brand"
                                >
                                    <Mail className="size-4 shrink-0 text-text-muted" />
                                    <span className="truncate">{listing.contactEmail}</span>
                                </a>
                            )}

                            {listing.contactPhone && (
                                <a
                                    href={`tel:${listing.contactPhone}`}
                                    className="flex items-center gap-3 text-text transition-colors hover:text-brand"
                                >
                                    <Phone className="size-4 shrink-0 text-text-muted" />
                                    <span>{listing.contactPhone}</span>
                                </a>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </aside>
    )
}

import { Head, Link, usePage } from '@inertiajs/react'
import { ChevronLeft, MapPin } from 'lucide-react'
import ListingModerationActions from '@/Components/Admin/ListingModerationActions'
import ListingDetailRow from '@/Components/Listings/ListingDetailRow'
import ListingFacts from '@/Components/Listings/ListingFacts'
import ListingGallery from '@/Components/Listings/ListingGallery'
import ListingSidebar from '@/Components/Listings/ListingSidebar'
import { formatDateTime } from '@/Components/Listings/listingFormat'
import FlashBanner from '@/Components/UI/FlashBanner'
import StatusBadge from '@/Components/UI/StatusBadge'
import AdminLayout from '@/Layouts/AdminLayout'
import type { AdminListingReviewPageProps } from '@/types/admin'
import type { SharedProps } from '@/types/shared'

export default function Show({
    listing,
    canModerate,
    translations,
    listingTranslations,
    propertyTypeLabels,
}: AdminListingReviewPageProps) {
    const { flash } = usePage<SharedProps>().props
    const review = translations.review
    const show = listingTranslations
    const propertyLabel = propertyTypeLabels[listing.propertyType] ?? listing.propertyType
    const typeLabel = listing.listingType === 'rent' ? show.for_rent : show.for_sale

    const address = [listing.addressLine, listing.city, listing.country, listing.postalCode]
        .filter(Boolean)
        .join(', ')

    return (
        <AdminLayout>
            <Head title={listing.title} />

            {flash.success && <FlashBanner message={flash.success} variant="success" />}
            {flash.error && <FlashBanner message={flash.error} variant="error" />}

            <Link
                href="/admin/listings"
                className="inline-flex items-center gap-1 text-sm text-text-muted transition-colors hover:text-text"
            >
                <ChevronLeft className="size-4" />
                {review.back}
            </Link>

            <section className="mt-6 rounded-2xl border border-border bg-surface p-5 sm:p-6">
                <div className="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                    <div className="flex min-w-0 flex-col gap-4">
                        <div className="flex flex-wrap items-center gap-3">
                            <h2 className="text-xs font-medium uppercase tracking-wide text-text-muted">
                                {review.moderation_heading}
                            </h2>
                            <StatusBadge status={listing.status} label={review.statuses[listing.status]} />
                        </div>

                        <dl className="grid grid-cols-1 gap-x-10 gap-y-3 text-sm sm:grid-cols-2">
                            <div className="min-w-0">
                                <dt className="text-text-muted">{review.owner_label}</dt>
                                <dd className="mt-0.5 text-text">
                                    <span className="font-semibold">{listing.ownerName ?? ''}</span>
                                    <span className="text-text-muted"> • {review.owner_types[listing.ownerType]}</span>
                                </dd>
                                {listing.ownerEmail && (
                                    <dd className="truncate">
                                        <a
                                            href={`mailto:${listing.ownerEmail}`}
                                            className="text-text-soft transition-colors hover:text-brand"
                                        >
                                            {listing.ownerEmail}
                                        </a>
                                    </dd>
                                )}
                            </div>
                            <div>
                                <dt className="text-text-muted">{review.submitted_label}</dt>
                                <dd className="mt-0.5 text-text">{formatDateTime(listing.createdAt)}</dd>
                            </div>
                            {listing.expiresAt && (
                                <div>
                                    <dt className="text-text-muted">{review.expires_label}</dt>
                                    <dd className="mt-0.5 text-text">{formatDateTime(listing.expiresAt)}</dd>
                                </div>
                            )}
                        </dl>

                        {listing.rejectionReason && (
                            <div className="text-sm">
                                <p className="text-text-muted">{review.rejection_reason_label}</p>
                                <p className="mt-0.5 whitespace-pre-line text-status-rejected">
                                    {listing.rejectionReason}
                                </p>
                            </div>
                        )}
                    </div>

                    {canModerate && (
                        <div className="flex flex-wrap items-center gap-3 md:shrink-0">
                            <ListingModerationActions
                                listingId={listing.id}
                                listingTitle={listing.title}
                                translations={translations}
                            />
                        </div>
                    )}
                </div>
            </section>

            <div className="mt-8 flex flex-col gap-3">
                <div className="flex flex-wrap gap-2">
                    <span className="rounded-md bg-surface-dark px-2.5 py-1 text-xs font-medium text-text-inverse">
                        {typeLabel}
                    </span>
                    <span className="rounded-md border border-border bg-surface px-2.5 py-1 text-xs font-medium text-text">
                        {propertyLabel}
                    </span>
                </div>

                <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">{listing.title}</h1>

                <div className="flex items-center gap-1.5 text-text-muted">
                    <MapPin className="size-4" />
                    <span>
                        {listing.city}, {listing.country}
                    </span>
                </div>
            </div>

            {listing.images.length > 0 && (
                <div className="mt-8">
                    <ListingGallery images={listing.images} title={listing.title} />
                </div>
            )}

            <div className="mt-8 grid grid-cols-1 gap-8 xl:grid-cols-3">
                <div className="flex flex-col gap-10 xl:col-span-2">
                    <ListingFacts listing={listing} translations={show} />

                    <section>
                        <h2 className="font-display text-xl font-semibold text-text">{show.description_heading}</h2>
                        <p className="mt-4 max-w-prose whitespace-pre-line leading-8 text-text-soft">
                            {listing.description}
                        </p>
                    </section>

                    <section>
                        <h2 className="font-display text-xl font-semibold text-text">{show.details_heading}</h2>
                        <dl className="mt-4 flex flex-col gap-4">
                            <ListingDetailRow label={show.detail_address} value={address} />
                            <ListingDetailRow label={show.detail_property_type} value={propertyLabel} />
                            <ListingDetailRow label={show.detail_listing_type} value={typeLabel} />
                            {listing.yearBuilt !== null && (
                                <ListingDetailRow label={show.detail_year_built} value={String(listing.yearBuilt)} />
                            )}
                            {listing.approvedAt && (
                                <ListingDetailRow label={show.detail_listed} value={listing.approvedAt} />
                            )}
                        </dl>
                    </section>
                </div>

                <div className="xl:col-span-1">
                    <ListingSidebar
                        listing={listing}
                        isBookmarked={false}
                        canBookmark={false}
                        canStartChat={false}
                        translations={show}
                    />
                </div>
            </div>
        </AdminLayout>
    )
}

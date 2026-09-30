import { Head, Link, usePage } from '@inertiajs/react'
import { ChevronLeft } from 'lucide-react'
import ListingModerationActions from '@/Components/Admin/ListingModerationActions'
import ListingReportActions from '@/Components/Admin/ListingReportActions'
import ListingReportList from '@/Components/Admin/ListingReportList'
import ListingDetailHeader from '@/Components/Listings/ListingDetailHeader'
import ListingDetailSections from '@/Components/Listings/ListingDetailSections'
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
    amenityLabels,
    reportModeration,
    reportTranslations,
    reasonLabels,
}: AdminListingReviewPageProps) {
    const { flash } = usePage<SharedProps>().props
    const review = translations.review
    const show = listingTranslations
    const propertyLabel = propertyTypeLabels[listing.propertyType] ?? listing.propertyType

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

            {reportModeration && (
                <section className="mt-4 rounded-2xl border border-status-rejected/30 bg-surface p-5 sm:p-6">
                    <div className="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-3">
                                <h2 className="text-xs font-medium uppercase tracking-wide text-text-muted">
                                    {reportTranslations.panel_heading}
                                </h2>
                                <span className="rounded-full bg-status-rejected/10 px-2.5 py-0.5 text-xs font-semibold text-status-rejected">
                                    {(reportModeration.openReportsCount === 1
                                        ? reportTranslations.reports_count_one
                                        : reportTranslations.reports_count_other
                                    ).replace(':count', String(reportModeration.openReportsCount))}
                                </span>
                            </div>
                            <div className="mt-4">
                                <ListingReportList
                                    reports={reportModeration.reports}
                                    translations={reportTranslations}
                                    reasonLabels={reasonLabels}
                                />
                            </div>
                            {!reportModeration.canTakeDown && (
                                <p className="mt-3 text-sm text-text-muted">{reportTranslations.not_approved}</p>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center gap-3 md:shrink-0">
                            <ListingReportActions listing={reportModeration} translations={reportTranslations} />
                        </div>
                    </div>
                </section>
            )}

            <div className="mt-8 flex flex-col gap-8 text-ink">
                <ListingDetailHeader
                    listing={listing}
                    translations={show}
                    propertyLabel={propertyLabel}
                    showBack={false}
                />
                <ListingGallery images={listing.images} title={listing.title} translations={show.gallery} />
                <ListingFacts listing={listing} translations={show} />
                <ListingDetailSections
                    listing={listing}
                    translations={show}
                    propertyLabel={propertyLabel}
                    amenityLabels={amenityLabels}
                    sidebar={<ListingSidebar listing={listing} translations={show} />}
                />
            </div>
        </AdminLayout>
    )
}

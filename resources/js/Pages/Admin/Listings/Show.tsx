import { Head, usePage } from '@inertiajs/react'
import ListingModerationPanel from '@/Components/Admin/ListingModerationPanel'
import ListingReportPanel from '@/Components/Admin/ListingReportPanel'
import ListingDetailHeader from '@/Components/Listings/ListingDetailHeader'
import ListingDetailSections from '@/Components/Listings/ListingDetailSections'
import ListingFacts from '@/Components/Listings/ListingFacts'
import ListingGallery from '@/Components/Listings/ListingGallery'
import ListingSidebar from '@/Components/Listings/ListingSidebar'
import Button from '@/Components/UI/Button'
import FlashBanner from '@/Components/UI/FlashBanner'
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
    const show = listingTranslations
    const propertyLabel = propertyTypeLabels[listing.propertyType] ?? listing.propertyType

    return (
        <AdminLayout>
            <Head title={listing.title} />

            <Button variant="link" href="/admin/listings" className="self-start">
                <span aria-hidden="true">← </span>
                {translations.review.back}
            </Button>

            {flash.success && <FlashBanner message={flash.success} variant="success" className="" />}
            {flash.error && <FlashBanner message={flash.error} variant="error" className="" />}

            <div className="@container/review">
                <div className="flex flex-col gap-8">
                    <ListingModerationPanel
                        listing={listing}
                        canModerate={canModerate}
                        translations={translations}
                        className="flex @4xl/review:hidden"
                    />

                    {reportModeration && (
                        <ListingReportPanel
                            report={reportModeration}
                            translations={reportTranslations}
                            reasonLabels={reasonLabels}
                        />
                    )}

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
                        sidebar={
                            <ListingSidebar
                                listing={listing}
                                translations={show}
                                actions={
                                    <ListingModerationPanel
                                        listing={listing}
                                        canModerate={canModerate}
                                        translations={translations}
                                        className="hidden @4xl/review:flex"
                                    />
                                }
                            />
                        }
                    />
                </div>
            </div>
        </AdminLayout>
    )
}

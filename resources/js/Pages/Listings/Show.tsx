import { useState } from 'react'
import { Head, usePage } from '@inertiajs/react'
import ListingBookmarkHeart from '@/Components/Listings/ListingBookmarkHeart'
import ListingContactBlock from '@/Components/Listings/ListingContactBlock'
import ListingDetailActions from '@/Components/Listings/ListingDetailActions'
import ListingDetailBlock from '@/Components/Listings/ListingDetailBlock'
import ListingDetailHeader from '@/Components/Listings/ListingDetailHeader'
import ListingDetailSections from '@/Components/Listings/ListingDetailSections'
import ListingFacts from '@/Components/Listings/ListingFacts'
import ListingGallery from '@/Components/Listings/ListingGallery'
import ListingMobileBar from '@/Components/Listings/ListingMobileBar'
import ListingSidebar from '@/Components/Listings/ListingSidebar'
import { hasListingContact } from '@/Components/Listings/listingFormat'
import MortgageCalculator from '@/Components/Listings/MortgageCalculator'
import { useMortgageEstimate } from '@/Components/Listings/mortgage'
import ReportListingButton from '@/Components/Listings/ReportListingButton'
import SimilarListings from '@/Components/Listings/SimilarListings'
import FlashBanner from '@/Components/UI/FlashBanner'
import PublicLayout from '@/Layouts/PublicLayout'
import type { ListingShowPageProps } from '@/types/listing'
import type { SharedProps } from '@/types/shared'

const gutterClass = 'px-5 md:px-10 lg:px-16'

export default function Show({
    listing,
    isBookmarked,
    canBookmark,
    canStartChat,
    canReport,
    similarListings,
    mortgageDefaults,
    translations,
    reportTranslations,
}: ListingShowPageProps) {
    const { flash, ui } = usePage<SharedProps>().props
    const [dismissedFlash, setDismissedFlash] = useState<string | null>(null)
    const mortgage = useMortgageEstimate(listing.price, mortgageDefaults)
    const show = translations.show
    const isSale = listing.listingType === 'sale'
    const propertyLabel = translations.filters.property_types[listing.propertyType] ?? listing.propertyType
    const flashMessage = flash.success ?? flash.error
    const browseHref = `/listings?listingType=${listing.listingType}&propertyType=${listing.propertyType}`

    const flashBanner = flashMessage && dismissedFlash !== flashMessage && (
        <FlashBanner
            message={flashMessage}
            variant={flash.success ? 'success' : 'error'}
            className=""
            onDismiss={() => setDismissedFlash(flashMessage)}
            dismissLabel={ui.dismiss}
        />
    )

    const reportButton = canReport && <ReportListingButton listingId={listing.id} translations={reportTranslations} />

    return (
        <PublicLayout
            back={{ href: '/listings', label: show.back }}
            backAction={
                canBookmark ? (
                    <ListingBookmarkHeart listingId={listing.id} isBookmarked={isBookmarked} translations={show} />
                ) : undefined
            }
        >
            <Head title={listing.title} />

            <div className="mx-auto flex max-w-360 flex-col pb-30">
                <div className="flex flex-col">
                    <div className={`${gutterClass} pt-7 pb-6 lg:pt-8 lg:pb-12`}>
                        <ListingDetailHeader listing={listing} translations={show} propertyLabel={propertyLabel} />
                    </div>

                    <div className="max-lg:order-first lg:px-16">
                        <ListingGallery images={listing.images} title={listing.title} translations={show.gallery} />
                    </div>
                </div>

                {flashBanner && <div className={`${gutterClass} pb-6 lg:hidden`}>{flashBanner}</div>}

                <div className={`${gutterClass} lg:pt-14`}>
                    <ListingFacts listing={listing} translations={show} />
                </div>

                <div className={`${gutterClass} pt-10 pb-12 lg:pt-20 lg:pb-30`}>
                    <ListingDetailSections
                        listing={listing}
                        translations={show}
                        propertyLabel={propertyLabel}
                        amenityLabels={translations.amenities}
                        mortgage={
                            isSale && (
                                <>
                                    <ListingDetailBlock heading={show.mortgage.heading} className="hidden lg:grid">
                                        <MortgageCalculator
                                            mortgage={mortgage}
                                            termOptions={mortgageDefaults.term_options}
                                            translations={show.mortgage}
                                            variant="full"
                                        />
                                    </ListingDetailBlock>
                                    <div className="lg:hidden">
                                        <MortgageCalculator
                                            mortgage={mortgage}
                                            termOptions={mortgageDefaults.term_options}
                                            translations={show.mortgage}
                                            variant="compact"
                                        />
                                    </div>
                                </>
                            )
                        }
                        footer={
                            (hasListingContact(listing) || canReport) && (
                                <section className="flex flex-col gap-2.5 lg:hidden">
                                    <ListingContactBlock listing={listing} translations={show} />
                                    {reportButton}
                                </section>
                            )
                        }
                        sidebar={
                            <ListingSidebar
                                listing={listing}
                                translations={show}
                                className="hidden lg:flex"
                                actions={
                                    <>
                                        <ListingDetailActions
                                            listingId={listing.id}
                                            isBookmarked={isBookmarked}
                                            canBookmark={canBookmark}
                                            canStartChat={canStartChat}
                                            translations={show}
                                        />
                                        {flashBanner}
                                    </>
                                }
                                footer={reportButton}
                            />
                        }
                    />
                </div>

                <div className={gutterClass}>
                    <SimilarListings
                        listings={similarListings}
                        translations={show.similar}
                        perMonth={translations.card.per_month}
                        browseHref={browseHref}
                    />
                </div>
            </div>

            {canStartChat && (
                <ListingMobileBar
                    listing={listing}
                    translations={show}
                    monthlyPayment={isSale ? (mortgage.estimate?.monthlyPayment ?? null) : null}
                />
            )}
        </PublicLayout>
    )
}

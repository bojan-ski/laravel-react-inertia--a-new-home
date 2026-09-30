import { Head, Link, usePage } from '@inertiajs/react'
import AccountPageHeader from '@/Components/Account/AccountPageHeader'
import BookmarkCard from '@/Components/Bookmarks/BookmarkCard'
import Button from '@/Components/UI/Button'
import EmptyState from '@/Components/UI/EmptyState'
import FlashBanner from '@/Components/UI/FlashBanner'
import PublicLayout from '@/Layouts/PublicLayout'
import type { BookmarksIndexPageProps } from '@/types/bookmark'
import type { SharedProps } from '@/types/shared'

const gridClasses = 'grid grid-cols-1 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-4'

export default function Index({
    listings,
    closedListings,
    canManageSavedSearches,
    translations,
    listingTranslations,
}: BookmarksIndexPageProps) {
    const { flash } = usePage<SharedProps>().props
    const countNoun = listings.length === 1 ? translations.count_one : translations.count_other
    const perMonth = listingTranslations.card.per_month

    return (
        <PublicLayout>
            <Head title={translations.title} />

            <AccountPageHeader
                title={translations.title}
                subtitle={translations.subtitle}
                action={
                    canManageSavedSearches && (
                        <Link
                            href="/saved-searches"
                            className="border-b border-current pb-1 text-sm transition-colors duration-150 hover:text-forest"
                        >
                            {translations.saved_searches_link} <span aria-hidden="true">→</span>
                        </Link>
                    )
                }
            />

            <div className="flex flex-col gap-8 px-5 pt-8 pb-20 md:px-10 xl:px-16">
                {flash.success && <FlashBanner message={flash.success} variant="success" className="" />}

                {listings.length === 0 ? (
                    <EmptyState
                        title={translations.empty.headline}
                        action={
                            <Button href="/listings" variant="secondary" size="sm" className="mt-2">
                                {translations.empty.cta}
                            </Button>
                        }
                    />
                ) : (
                    <>
                        <p className="text-sm text-ink-muted">
                            {translations.count_prefix} {listings.length} {countNoun}
                        </p>

                        <div className={`${gridClasses} sm:gap-y-12`}>
                            {listings.map((listing) => (
                                <BookmarkCard
                                    key={listing.id}
                                    listing={listing}
                                    variant="active"
                                    perMonth={perMonth}
                                    removeLabel={translations.remove}
                                />
                            ))}
                        </div>
                    </>
                )}

                {closedListings.length > 0 && (
                    <section aria-labelledby="closed-bookmarks-heading" className="flex flex-col gap-2 lg:gap-8">
                        <div className="mt-4 flex flex-col gap-1.5 border-t border-rule pt-8 lg:mt-8 lg:gap-2 lg:pt-12">
                            <h2
                                id="closed-bookmarks-heading"
                                className="text-[28px] font-airy tracking-[-0.02em] lg:text-[40px] lg:tracking-[-0.03em]"
                            >
                                {translations.closed.heading}
                            </h2>
                            <p className="text-[13px] leading-normal text-ink-muted lg:text-[15px]">
                                {translations.closed.subtitle}
                            </p>
                        </div>

                        <div className={`${gridClasses} sm:gap-y-6`}>
                            {closedListings.map((listing) => (
                                <BookmarkCard
                                    key={listing.id}
                                    listing={listing}
                                    variant="closed"
                                    closedLabel={
                                        listing.status === 'approved'
                                            ? translations.closed.expired
                                            : translations.closed.status[listing.status]
                                    }
                                    perMonth={perMonth}
                                    removeLabel={translations.remove}
                                />
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </PublicLayout>
    )
}

import { Head, Link } from '@inertiajs/react'
import { Plus, Settings } from 'lucide-react'
import ProfileListingRow from '@/Components/Profile/ProfileListingRow'
import PublicLayout from '@/Layouts/PublicLayout'
import type { ProfileIndexPageProps } from '@/types/profile'

export default function Index({ listings, translations, propertyTypeLabels }: ProfileIndexPageProps) {
    return (
        <PublicLayout>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto flex max-w-7xl flex-col gap-6 px-6 py-10 sm:flex-row sm:items-start sm:justify-between md:py-14">
                    <div>
                        <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">
                            {translations.title}
                        </h1>
                        <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Link
                            href="/profile/settings"
                            className="inline-flex items-center gap-2 rounded-full border border-border-strong px-5 py-2.5 text-sm font-medium text-text-soft transition-colors hover:border-text hover:text-text"
                        >
                            <Settings className="size-4" />
                            {translations.account_settings}
                        </Link>
                        <Link
                            href="/my-listings/create"
                            className="inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark"
                        >
                            <Plus className="size-4" />
                            {translations.new_listing}
                        </Link>
                    </div>
                </div>
            </div>

            <div className="mx-auto max-w-7xl px-6 py-8">
                {listings.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 py-24 text-center">
                        <p className="text-lg font-semibold text-text">{translations.empty.headline}</p>
                        <p className="max-w-md text-text-soft">{translations.empty.subline}</p>
                        <Link
                            href="/my-listings/create"
                            className="mt-2 inline-flex items-center gap-2 rounded-full bg-brand px-6 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark"
                        >
                            <Plus className="size-4" />
                            {translations.empty.cta}
                        </Link>
                    </div>
                ) : (
                    <div className="flex flex-col gap-4">
                        {listings.map((listing) => (
                            <ProfileListingRow
                                key={listing.id}
                                listing={listing}
                                translations={translations}
                                propertyTypeLabels={propertyTypeLabels}
                            />
                        ))}
                    </div>
                )}
            </div>
        </PublicLayout>
    )
}

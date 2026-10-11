import { Head } from '@inertiajs/react'
import AccountPageHeader from '@/Components/Account/AccountPageHeader'
import ProfileListingRow from '@/Components/Profile/ProfileListingRow'
import Button from '@/Components/UI/Button'
import EmptyState from '@/Components/UI/EmptyState'
import useReveal from '@/Components/UI/useReveal'
import PublicLayout from '@/Layouts/PublicLayout'
import type { ProfileIndexPageProps } from '@/types/profile'

export default function Index({ listings, translations }: ProfileIndexPageProps) {
    const reveal = useReveal<HTMLUListElement>('left', listings)
    const newListing = (fullWidth: boolean) => (
        <Button
            href="/my-listings/create"
            fullWidth={fullWidth}
            trailing={<span aria-hidden="true">+</span>}
            className={`min-h-13 ${fullWidth ? 'mt-3' : 'gap-10!'}`}
        >
            {translations.new_listing}
        </Button>
    )

    return (
        <PublicLayout contained>
            <Head title={translations.title} />

            <AccountPageHeader
                title={translations.title}
                subtitle={translations.subtitle}
                action={newListing(false)}
                mobileAction={newListing(true)}
            />

            <div className="px-5 pt-2 pb-20 md:px-10 xl:px-16">
                {listings.length === 0 ? (
                    <EmptyState
                        className="mt-8"
                        title={translations.empty.headline}
                        hint={translations.empty.subline}
                        action={
                            <Button href="/my-listings/create" variant="secondary" size="sm" className="mt-2">
                                {translations.empty.cta}
                            </Button>
                        }
                    />
                ) : (
                    <ul {...reveal}>
                        {listings.map((listing) => (
                            <ProfileListingRow key={listing.id} listing={listing} translations={translations} />
                        ))}
                    </ul>
                )}
            </div>
        </PublicLayout>
    )
}

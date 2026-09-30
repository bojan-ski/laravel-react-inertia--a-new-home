import { Link } from '@inertiajs/react'
import { Mail, Phone } from 'lucide-react'
import { hasListingContact } from '@/Components/Listings/listingFormat'
import Kicker from '@/Components/UI/Kicker'
import type { ListingContactBlockProps } from '@/types/listing'

const rowClass = 'flex min-h-8 items-center gap-3 text-sm lg:min-h-0 lg:text-[15px]'

export default function ListingContactBlock({ listing, translations }: ListingContactBlockProps) {
    if (!hasListingContact(listing)) {
        return null
    }

    return (
        <div className="flex flex-col gap-2.5 lg:gap-3.5">
            <Kicker as="h2" size="sm" className="lg:text-xs">
                {translations.contact_heading}
            </Kicker>

            {listing.contactName && (
                <span className="text-base font-[450] lg:text-[17px]">{listing.contactName}</span>
            )}

            {listing.contactEmail && (
                <a href={`mailto:${listing.contactEmail}`} className={rowClass}>
                    <Mail className="hidden size-4 shrink-0 lg:block" strokeWidth={1.25} aria-hidden="true" />
                    <span className="min-w-0 truncate">{listing.contactEmail}</span>
                </a>
            )}

            {listing.contactPhone && (
                <a href={`tel:${listing.contactPhone}`} className={rowClass}>
                    <Phone className="hidden size-4 shrink-0 lg:block" strokeWidth={1.25} aria-hidden="true" />
                    <span>{listing.contactPhone}</span>
                </a>
            )}

            {listing.agency && (
                <Link
                    href={`/agencies/${listing.agency.slug}`}
                    className="mt-1 self-start text-sm text-forest hover:text-forest-600"
                >
                    {translations.view_agency} <span aria-hidden="true">→</span>
                </Link>
            )}
        </div>
    )
}

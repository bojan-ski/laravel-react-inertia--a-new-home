import ListingContactBlock from '@/Components/Listings/ListingContactBlock'
import { formatPrice, hasListingContact } from '@/Components/Listings/listingFormat'
import type { ListingSidebarProps } from '@/types/listing'

export default function ListingSidebar({ listing, translations, actions, footer, className = 'flex' }: ListingSidebarProps) {
    return (
        <aside className={`sticky top-8 flex-col gap-7 self-start border-t border-ink pt-7 @6xl:col-start-3 ${className}`}>
            <p className="text-[40px] leading-none font-airy tracking-[-0.03em]">
                {formatPrice(listing.price, listing.currency)}
                {listing.listingType === 'rent' && (
                    <span className="text-[15px] font-normal tracking-normal"> {translations.per_month}</span>
                )}
            </p>

            {actions}

            {hasListingContact(listing) && (
                <div className="border-t border-rule pt-6">
                    <ListingContactBlock listing={listing} translations={translations} />
                </div>
            )}

            {footer && <div className="border-t border-rule pt-5">{footer}</div>}
        </aside>
    )
}

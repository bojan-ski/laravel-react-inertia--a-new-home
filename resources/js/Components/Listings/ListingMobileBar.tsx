import { formatPrice } from '@/Components/Listings/listingFormat'
import { useStartChat } from '@/Components/Listings/useListingActions'
import Button from '@/Components/UI/Button'
import type { ListingMobileBarProps } from '@/types/listing'

export default function ListingMobileBar({ listing, translations, monthlyPayment }: ListingMobileBarProps) {
    const chat = useStartChat(listing.id)
    const isRental = listing.listingType === 'rent'

    return (
        <div className="sticky bottom-0 z-10 grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 border-t border-rule bg-paper px-5 pt-3 pb-4 md:px-10 lg:hidden">
            <div className="flex min-w-0 flex-col">
                <span className="text-lg">
                    {formatPrice(listing.price, listing.currency)}
                    {isRental && <span className="text-sm"> {translations.per_month}</span>}
                </span>
                {!isRental && monthlyPayment !== null && (
                    <span className="text-xs text-ink/62">
                        {translations.month_estimate.replace(
                            ':amount',
                            formatPrice(String(monthlyPayment), listing.currency),
                        )}
                    </span>
                )}
            </div>

            <Button
                className="min-h-13 gap-7"
                onClick={chat.start}
                disabled={chat.processing}
                trailing={<span aria-hidden="true">→</span>}
            >
                {translations.start_chat}
            </Button>
        </div>
    )
}

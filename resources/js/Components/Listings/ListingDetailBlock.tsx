import Kicker from '@/Components/UI/Kicker'
import type { ListingDetailBlockProps } from '@/types/listing'

export default function ListingDetailBlock({
    heading,
    headingClassName = '@6xl:pt-1',
    className = 'grid',
    children,
}: ListingDetailBlockProps) {
    return (
        <section className={`gap-3.5 @6xl:grid-cols-[200px_minmax(0,1fr)] @6xl:gap-8 ${className}`}>
            <Kicker as="h2" size="sm" className={`@4xl:text-xs ${headingClassName}`}>
                {heading}
            </Kicker>
            <div className="min-w-0">{children}</div>
        </section>
    )
}

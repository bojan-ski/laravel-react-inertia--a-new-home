import { websiteHost } from '@/Components/Agencies/agencyFormat'
import Kicker from '@/Components/UI/Kicker'
import type { AgencyContactListProps } from '@/types/agency'

const linkClass =
    'flex min-h-10 items-center text-[15px] wrap-break-word transition-colors duration-150 lg:min-h-0 lg:text-base'

export default function AgencyContactList({ agency, heading }: AgencyContactListProps) {
    return (
        <div className="flex flex-col gap-1 lg:gap-4">
            <Kicker as="h2" size="sm" className="mb-1.5 lg:mb-0 lg:text-xs">
                {heading}
            </Kicker>

            <a href={`mailto:${agency.email}`} className={`${linkClass} hover:text-forest`}>
                <span className="min-w-0">{agency.email}</span>
            </a>

            {agency.phone && (
                <a href={`tel:${agency.phone}`} className={`${linkClass} hover:text-forest`}>
                    {agency.phone}
                </a>
            )}

            {agency.website && (
                <a
                    href={agency.website}
                    target="_blank"
                    rel="noopener noreferrer"
                    className={`${linkClass} text-forest hover:text-forest-600`}
                >
                    <span className="min-w-0">
                        {websiteHost(agency.website)}
                        <span aria-hidden="true"> ↗</span>
                    </span>
                </a>
            )}

            {agency.address && (
                <p className="text-[15px]/normal whitespace-pre-line text-ink/70">{agency.address}</p>
            )}
        </div>
    )
}

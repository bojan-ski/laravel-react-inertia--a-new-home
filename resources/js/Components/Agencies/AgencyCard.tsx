import { Link } from '@inertiajs/react'
import { ArrowRight, BadgeCheck, MapPin } from 'lucide-react'
import AgencyContactList from '@/Components/Agencies/AgencyContactList'
import { agencyRegistrationLines } from '@/Components/Agencies/agencyFormat'
import type { AgencyCardProps } from '@/types/agency'

export default function AgencyCard({ agency, viewLabel, registrationLabels }: AgencyCardProps) {
    const href = `/agencies/${agency.slug}`
    const registration = agencyRegistrationLines(agency, registrationLabels)

    return (
        <article className="flex flex-col rounded-xl border border-border bg-surface p-6 shadow-sm transition-shadow hover:shadow-md">
            <h2 className="line-clamp-2 font-display text-xl font-semibold text-text">
                <Link href={href} className="transition-colors hover:text-brand">
                    {agency.name}
                </Link>
            </h2>

            {agency.address && (
                <p className="mt-1.5 flex items-start gap-1.5 text-sm text-text-muted">
                    <MapPin className="mt-0.5 size-4 shrink-0" />
                    <span>{agency.address}</span>
                </p>
            )}

            {registration.length > 0 && (
                <p className="mt-1 flex items-start gap-1.5 text-xs text-text-muted">
                    <BadgeCheck className="size-4 shrink-0" />
                    <span className="flex flex-wrap gap-x-3 gap-y-0.5">
                        {registration.map((line) => (
                            <span key={line}>{line}</span>
                        ))}
                    </span>
                </p>
            )}

            {agency.description && (
                <p className="mt-4 line-clamp-3 text-sm leading-relaxed text-text-soft">{agency.description}</p>
            )}

            <div className="mt-5 border-t border-border pt-4">
                <AgencyContactList agency={agency} />
            </div>

            <Link
                href={href}
                className="mt-5 inline-flex items-center gap-1.5 self-start text-sm font-semibold text-brand transition-colors hover:text-brand-dark"
            >
                {viewLabel}
                <ArrowRight className="size-4" />
            </Link>
        </article>
    )
}

import { Globe, Mail, MapPin, Phone } from 'lucide-react'
import type { AgencyCardProps } from '@/types/agency'

export default function AgencyCard({ agency }: AgencyCardProps) {
    return (
        <article className="flex flex-col rounded-xl border border-border bg-surface p-6 shadow-sm">
            <h2 className="line-clamp-2 font-display text-xl font-semibold text-text">{agency.name}</h2>

            {agency.address && (
                <p className="mt-1.5 flex items-start gap-1.5 text-sm text-text-muted">
                    <MapPin className="mt-0.5 size-4 shrink-0" />
                    <span>{agency.address}</span>
                </p>
            )}

            {agency.description && (
                <p className="mt-4 line-clamp-3 text-sm leading-relaxed text-text-soft">{agency.description}</p>
            )}

            <div className="mt-5 flex flex-col gap-1.5 border-t border-border pt-4 text-sm text-text-muted">
                <a
                    href={`mailto:${agency.email}`}
                    className="flex items-center gap-2 transition-colors hover:text-text"
                >
                    <Mail className="size-4 shrink-0" />
                    <span className="truncate">{agency.email}</span>
                </a>

                {agency.phone && (
                    <a
                        href={`tel:${agency.phone}`}
                        className="flex items-center gap-2 transition-colors hover:text-text"
                    >
                        <Phone className="size-4 shrink-0" />
                        <span className="truncate">{agency.phone}</span>
                    </a>
                )}

                {agency.website && (
                    <a
                        href={agency.website}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-2 text-brand transition-colors hover:text-brand-dark"
                    >
                        <Globe className="size-4 shrink-0" />
                        <span className="truncate">{agency.website}</span>
                    </a>
                )}
            </div>
        </article>
    )
}
